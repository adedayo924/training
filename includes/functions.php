<?php
/**
 * Core Helper Functions
 *
 * Utility functions used throughout the application.
 */

/**
 * Sanitize input string.
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Alias for sanitize — output escaping.
 */
function e(string $input): string
{
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a URL-friendly slug from a string.
 */
function generateSlug(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Format currency value.
 */
function formatCurrency(float $amount): string
{
    static $symbol = null;
    if ($symbol === null) {
        $symbol = getSetting('currency_symbol', CURRENCY_SYMBOL);
    }
    return $symbol . number_format($amount, 2);
}

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Set a flash message.
 *
 * @param string $type    success, error, warning, info
 * @param string $message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8')];
}

/**
 * Get and clear flash message.
 *
 * @return array|null
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if the user is logged in as admin.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Get the current admin's ID.
 */
function getAdminId(): ?int
{
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Generate a CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF field for forms.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Verify a CSRF token.
 */
function verifyCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF from POST data. Returns false if invalid and redirects.
 */
function requireCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        setFlash('error', 'Invalid security token. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? APP_URL);
    }
    return true;
}

/**
 * Log an error to the log file.
 */
function logError(string $message, ?Throwable $e = null): void
{
    $logFile = LOGS_PATH . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $entry = "[{$timestamp}] [{$ip}] {$message}";

    if ($e) {
        $entry .= " | File: " . $e->getFile() . " Line: " . $e->getLine();
        $entry .= " | " . $e->getMessage();
    }

    $entry .= PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Sanitize a string for use as a filename.
 */
function sanitizeFilename(string $filename): string
{
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    $filename = preg_replace('/\.(?=.*\.)/', '', $filename);
    return $filename;
}

/**
 * Settings cache (shared per-request).
 */
function &settingsCache(): array
{
    static $cache = [];
    return $cache;
}

/**
 * Get a setting from the database (cached per request).
 */
function getSetting(string $key, string $default = ''): string
{
    $cache = &settingsCache();
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    $cache[$key] = $row ? $row['setting_value'] : $default;
    return $cache[$key];
}

/**
 * Update a setting in the database.
 */
function updateSetting(string $key, string $value): bool
{
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $result = $stmt->execute([$key, $value, $value]);
    $cache = &settingsCache();
    unset($cache[$key]);
    return $result;
}

/**
 * Get time elapsed since a given datetime.
 */
function timeElapsed(string $datetime): string
{
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Generate a unique reference string for payments.
 */
function generateReference(string $prefix = 'JCDW'): string
{
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(6))) . '-' . time();
}

/**
 * Log an admin action to the audit trail.
 */
function logAdminAction(string $action, string $details = ''): void
{
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO audit_log (admin_id, admin_email, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['admin_id'] ?? null,
            $_SESSION['admin_email'] ?? 'system',
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    } catch (PDOException $e) {
        // Silently fail — don't break the app for audit logging
    }
}

/**
 * Escape special characters for use in LIKE queries.
 */
function escapeLike(string $input): string
{
    return addcslashes($input, '%_\\');
}

/**
 * Get Bootstrap class for payment status badge.
 */
function paymentStatusClass(string $status): string
{
    return match($status) {
        'paid'    => 'success',
        'free'    => 'info',
        'pending' => 'warning',
        'failed'  => 'danger',
        default   => 'secondary',
    };
}

/**
 * Get Bootstrap class for event type badge.
 */
function eventTypeClass(string $type): string
{
    return match($type) {
        'online'    => 'primary',
        'in-person' => 'success',
        'hybrid'    => 'info',
        default     => 'secondary',
    };
}

/**
 * Get Bootstrap class for event status badge.
 */
function eventStatusClass(string $status): string
{
    return match($status) {
        'upcoming'  => 'success',
        'ongoing'   => 'primary',
        'completed' => 'secondary',
        'cancelled' => 'danger',
        default     => 'secondary',
    };
}

/**
 * Render a payment status badge.
 */
function paymentStatusBadge(string $status): string
{
    $class = paymentStatusClass($status);
    return '<span class="badge bg-' . $class . '">' . ucfirst(e($status)) . '</span>';
}

/**
 * Render an event type badge.
 */
function eventTypeBadge(string $type): string
{
    $class = eventTypeClass($type);
    return '<span class="badge bg-' . $class . '">' . ucfirst(e($type)) . '</span>';
}

/**
 * Format a date string consistently.
 */
function formatDate(string $date, string $format = 'M d, Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Format a time string consistently.
 */
function formatTime(string $time): string
{
    return date('h:i A', strtotime($time));
}
