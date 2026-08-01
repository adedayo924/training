<?php
/**
 * Admin Logout
 *
 * Destroys the session and redirects to login.
 */

session_start();
session_unset();
session_destroy();

// Start a new session for flash message
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

setFlash('success', 'You have been logged out successfully.');
redirect(APP_URL . '/admin/login.php');
