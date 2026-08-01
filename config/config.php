<?php
/**
 * Application Configuration
 *
 * Loads .env file and defines all application constants.
 * To deploy to a new server, only edit the .env file.
 */

require_once __DIR__ . '/Dotenv.php';

// Load .env file
$envPath = dirname(__DIR__) . '/.env';
Dotenv::load($envPath);

// Database
define('DB_HOST', Dotenv::get('DB_HOST', 'database host name'));
define('DB_NAME', Dotenv::get('DB_NAME', 'database name'));
define('DB_USER', Dotenv::get('DB_USER', 'database user'));
define('DB_PASS', Dotenv::get('DB_PASS', 'database password'));

// Paystack
define('PAYSTACK_PUBLIC_KEY', Dotenv::get('PAYSTACK_PUBLIC_KEY', ''));
define('PAYSTACK_SECRET_KEY', Dotenv::get('PAYSTACK_SECRET_KEY', ''));

// Application
define('APP_NAME', Dotenv::get('APP_NAME', 'Jeilo CDW'));
define('APP_URL', Dotenv::get('APP_URL', 'host url'));
define('APP_ENV', Dotenv::get('APP_ENV', 'production'));
define('APP_DEBUG', Dotenv::get('APP_DEBUG', 'false') === 'true');

// Mail
define('MAIL_FROM', Dotenv::get('MAIL_FROM', 'jeilocreativedesignworld@gmail.com'));
define('MAIL_FROM_NAME', Dotenv::get('MAIL_FROM_NAME', 'Jeilo CDW'));

// Currency
define('CURRENCY', Dotenv::get('CURRENCY', 'NGN'));
define('CURRENCY_SYMBOL', Dotenv::get('CURRENCY_SYMBOL', '₦'));

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('EVENTS_UPLOADS_PATH', UPLOADS_PATH . '/events');
define('LOGS_PATH', ROOT_PATH . '/logs');
