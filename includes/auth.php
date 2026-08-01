<?php
/**
 * Admin Authentication Guard
 *
 * Include this file at the top of any admin page that requires authentication.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        if (defined('APP_ENV') && APP_ENV === 'production') {
            ini_set('session.cookie_secure', '1');
        }
    } else {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please log in to access the admin panel.');
    redirect(APP_URL . '/admin/login.php');
}

// Session timeout: 24 hours
$sessionTimeout = 86400;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $sessionTimeout) {
    session_unset();
    session_destroy();
    setFlash('error', 'Your session has expired. Please log in again.');
    redirect(APP_URL . '/admin/login.php');
}
