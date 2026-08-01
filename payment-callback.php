<?php
/**
 * Paystack Payment Callback
 *
 * Handles the redirect after Paystack payment.
 * Verifies the transaction and updates registration status.
 */

ob_start();
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$reference = $_GET['reference'] ?? $_GET['trxref'] ?? '';

if (empty($reference)) {
    setFlash('error', 'Invalid payment reference.');
    redirect(APP_URL . '/events.php');
}

try {
    $pdo = getDB();

    // Verify transaction with Paystack
    $paystackSecretKey = getSetting('paystack_secret_key', PAYSTACK_SECRET_KEY);

    $sslVerify = (APP_ENV !== 'development');
    $curlOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $paystackSecretKey,
        ],
        CURLOPT_SSL_VERIFYPEER => $sslVerify,
        CURLOPT_TIMEOUT        => 30,
    ];

    if (!$sslVerify) {
        $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
    }

    $ch = curl_init("https://api.paystack.co/transaction/verify/{$reference}");
    curl_setopt_array($ch, $curlOpts);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        logError("Paystack verify cURL error: {$err}");
        setFlash('error', 'Payment verification failed. Please contact support.');
        redirect(APP_URL . '/events.php');
    }

    $paystackResponse = json_decode($response, true);

    if (!$paystackResponse || !$paystackResponse['status'] || $paystackResponse['data']['status'] !== 'success') {
        $status = $paystackResponse['data']['status'] ?? 'unknown';
        logError("Paystack verification failed for reference: {$reference}. Status: {$status}");

        // Mark registration as failed
        $stmt = $pdo->prepare("UPDATE registrations SET payment_status = 'failed' WHERE paystack_reference = ?");
        $stmt->execute([$reference]);

        setFlash('error', 'Payment was not successful. Please try again or contact support.');
        redirect(APP_URL . '/events.php');
    }

    // Payment successful — update registration
    $stmt = $pdo->prepare("
        UPDATE registrations
        SET payment_status = 'paid', amount_paid = ?
        WHERE paystack_reference = ? AND payment_status = 'pending'
    ");
    $amountPaid = $paystackResponse['data']['amount'] / 100; // Convert from kobo
    $stmt->execute([$amountPaid, $reference]);

    $statusChanged = $stmt->rowCount() > 0;

    if (!$statusChanged) {
        // Registration already updated or not found
        $check = $pdo->prepare("SELECT id, event_id, payment_status FROM registrations WHERE paystack_reference = ?");
        $check->execute([$reference]);
        $reg = $check->fetch();

        if (!$reg) {
            logError("Registration not found for reference: {$reference}");
            setFlash('error', 'Registration not found. Please contact support.');
            redirect(APP_URL . '/events.php');
        }

        // If already paid, just redirect to success
        if ($reg['payment_status'] === 'paid') {
            $stmt = $pdo->prepare("
                SELECT r.*, e.title as event_title, e.slug as event_slug, e.date as event_date,
                       e.time as event_time, e.type as event_type, e.venue, e.meeting_link
                FROM registrations r
                JOIN events e ON r.event_id = e.id
                WHERE r.paystack_reference = ?
            ");
            $stmt->execute([$reference]);
            $registration = $stmt->fetch();

            if ($registration) {
                $_SESSION['last_registration'] = [
                    'id'         => $registration['id'],
                    'name'       => $registration['full_name'],
                    'email'      => $registration['email'],
                    'event_name' => $registration['event_title'],
                    'event_date' => $registration['event_date'],
                    'event_time' => $registration['event_time'],
                    'event_type' => $registration['event_type'],
                    'amount'     => $registration['amount_paid'],
                    'reference'  => $reference,
                ];
                redirect(APP_URL . '/registration-success.php');
            }
        }
    }

    // Get registration details for the success page
    $stmt = $pdo->prepare("
        SELECT r.*, e.title as event_title, e.slug as event_slug, e.date as event_date,
               e.time as event_time, e.type as event_type, e.venue, e.meeting_link
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        WHERE r.paystack_reference = ?
    ");
    $stmt->execute([$reference]);
    $registration = $stmt->fetch();

    if ($registration) {
        // Only send email if status actually changed (prevents duplicate emails)
        if ($statusChanged) {
            // Send confirmation email
            require_once __DIR__ . '/includes/email.php';
            sendRegistrationEmail(
                $registration['email'],
                $registration['full_name'],
                [
                    'title' => $registration['event_title'],
                    'date'  => $registration['event_date'],
                    'time'  => $registration['event_time'],
                    'type'  => $registration['event_type'],
                    'venue' => $registration['venue'],
                    'meeting_link' => $registration['meeting_link'],
                ]
            );
        }

        // Store data in session for success page
        $_SESSION['last_registration'] = [
            'id'         => $registration['id'],
            'name'       => $registration['full_name'],
            'email'      => $registration['email'],
            'event_name' => $registration['event_title'],
            'event_date' => $registration['event_date'],
            'event_time' => $registration['event_time'],
            'event_type' => $registration['event_type'],
            'amount'     => $registration['amount_paid'],
            'reference'  => $reference,
        ];

        redirect(APP_URL . '/registration-success.php');
    } else {
        setFlash('error', 'Registration record not found. Please contact support.');
        redirect(APP_URL . '/events.php');
    }

} catch (PDOException $e) {
    logError("Payment callback DB error", $e);
    setFlash('error', 'A system error occurred. Please contact support with reference: ' . e($reference));
    redirect(APP_URL . '/events.php');
}
