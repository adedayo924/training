<?php
/**
 * Registration Handler (AJAX)
 *
 * Processes registration form submissions.
 * Returns JSON responses for AJAX requests.
 * For paid events: initializes Paystack and returns the authorization URL.
 * For free events: creates the registration immediately.
 */

ob_start();
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/RateLimiter.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrf($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token invalid. Please refresh the page.']);
    exit;
}

// Rate limit check
$email = trim($_POST['email'] ?? '');
$ip    = $_SERVER['REMOTE_ADDR'];
$rateLimiter = new RateLimiter();
if ($rateLimiter->isRateLimited($email, $ip)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Please wait 15 minutes and try again.']);
    exit;
}

// Get input
$eventId  = (int)($_POST['event_id'] ?? 0);
$fullName = trim($_POST['full_name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');

// Validation
$errors = [];

if ($eventId <= 0) {
    $errors[] = 'Invalid event.';
}
if (empty($fullName) || strlen($fullName) < 2) {
    $errors[] = 'Full name is required (minimum 2 characters).';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}
if (empty($phone) || strlen($phone) < 7) {
    $errors[] = 'A valid phone number is required.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $pdo = getDB();

    // Verify event exists and is upcoming
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND status = 'upcoming'");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Event not found or no longer available.']);
        exit;
    }

    // Check capacity
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
    $countStmt->execute([$eventId]);
    $currentRegs = $countStmt->fetchColumn();

    if ($event['capacity'] > 0 && $currentRegs >= $event['capacity']) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'This event has reached its maximum capacity.']);
        exit;
    }

    // Check duplicate registration
    $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND email = ?");
    $dupStmt->execute([$eventId, $email]);
    if ($dupStmt->fetchColumn() > 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'You have already registered for this event with this email address.']);
        exit;
    }

    // Determine payment status
    $isPaid = (float)$event['price'] > 0;

    if ($isPaid) {
        // Initialize Paystack transaction
        $reference = generateReference();
        $amountKobo = (int)($event['price'] * 100); // Paystack uses kobo

        $paystackSecretKey = getSetting('paystack_secret_key', PAYSTACK_SECRET_KEY);

        $callbackUrl = APP_URL . '/payment-callback.php';

        $sslVerify = (APP_ENV !== 'development');
        $curlOpts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'email'        => $email,
                'amount'       => $amountKobo,
                'reference'    => $reference,
                'callback_url' => $callbackUrl,
                'metadata'     => json_encode([
                    'event_id'  => $eventId,
                    'full_name' => $fullName,
                    'phone'     => $phone,
                ]),
            ]),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $paystackSecretKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_TIMEOUT        => 30,
        ];

        if (!$sslVerify) {
            $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        $ch = curl_init('https://api.paystack.co/transaction/initialize');
        curl_setopt_array($ch, $curlOpts);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            logError("Paystack init cURL error: {$err}");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Payment initialization failed. Please try again.']);
            exit;
        }

        $paystackResponse = json_decode($response, true);

        if (!$paystackResponse || !$paystackResponse['status']) {
            $msg = $paystackResponse['message'] ?? 'Payment initialization failed.';
            logError("Paystack init error: {$msg}");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Payment initialization failed: ' . $msg]);
            exit;
        }

        // Create pending registration
        $stmt = $pdo->prepare("
            INSERT INTO registrations (event_id, full_name, email, phone, payment_status, paystack_reference, amount_paid)
            VALUES (?, ?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([$eventId, $fullName, $email, $phone, $reference, $event['price']]);

        // Clear rate limit on success
        $rateLimiter->clearAttempts($email, $ip);

        // Regenerate CSRF token after successful submission
        unset($_SESSION['csrf_token']);

        echo json_encode([
            'success'    => true,
            'is_paid'    => true,
            'auth_url'   => $paystackResponse['data']['authorization_url'],
            'reference'  => $reference,
        ]);

    } else {
        // Free event — register immediately
        $stmt = $pdo->prepare("
            INSERT INTO registrations (event_id, full_name, email, phone, payment_status, amount_paid)
            VALUES (?, ?, ?, ?, 'free', 0)
        ");
        $stmt->execute([$eventId, $fullName, $email, $phone]);
        $registrationId = $pdo->lastInsertId();

        // Set cookie to prevent duplicate registration
        $cookieFlags = ['expires' => time() + (86400 * 30), 'path' => '/', 'httponly' => true, 'samesite' => 'Lax'];
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $cookieFlags['secure'] = true;
        }
        setcookie('jcdw_reg_' . $eventId, '1', $cookieFlags);

        // Clear rate limit on success
        $rateLimiter->clearAttempts($email, $ip);

        // Send confirmation email
        require_once __DIR__ . '/includes/email.php';
        sendRegistrationEmail($email, $fullName, $event);

        // Regenerate CSRF token
        unset($_SESSION['csrf_token']);

        echo json_encode([
            'success'         => true,
            'is_paid'         => false,
            'message'         => 'Registration successful!',
            'registration_id' => $registrationId,
            'event_slug'      => $event['slug'],
        ]);
    }

} catch (PDOException $e) {
    logError("Registration error", $e);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again.']);
}

ob_end_flush();
