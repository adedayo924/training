<?php
/**
 * Database Setup Script
 *
 * One-time installer that creates the database, tables, and default admin user.
 * DELETE THIS FILE after running it for security.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        try {
            // Connect without selecting a database first
            $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");

            // --- admins table ---
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS admins (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('super_admin', 'admin') DEFAULT 'admin',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // --- events table ---
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL UNIQUE,
                    description TEXT NOT NULL,
                    type ENUM('online', 'in-person', 'hybrid') NOT NULL DEFAULT 'online',
                    date DATE NOT NULL,
                    time TIME NOT NULL,
                    end_date DATE NULL,
                    end_time TIME NULL,
                    venue VARCHAR(255) NULL,
                    meeting_link VARCHAR(500) NULL,
                    capacity INT NOT NULL DEFAULT 0,
                    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    image VARCHAR(255) NULL,
                    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
                    is_featured TINYINT(1) DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_status (status),
                    INDEX idx_date (date),
                    INDEX idx_featured (is_featured)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // --- registrations table ---
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS registrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_id INT NOT NULL,
                    full_name VARCHAR(200) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(50) NOT NULL,
                    payment_status ENUM('free', 'pending', 'paid', 'failed') DEFAULT 'free',
                    paystack_reference VARCHAR(255) NULL,
                    amount_paid DECIMAL(10,2) DEFAULT 0.00,
                    whatsapp_sent TINYINT(1) DEFAULT 0,
                    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
                    INDEX idx_event_id (event_id),
                    INDEX idx_email (email),
                    INDEX idx_payment_status (payment_status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // --- settings table ---
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT NULL,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // --- login_attempts table ---
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email_ip (email, ip_address),
                    INDEX idx_attempted_at (attempted_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // --- audit_log table ---
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS audit_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    admin_id INT NULL,
                    admin_email VARCHAR(255) NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    details TEXT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_admin_id (admin_id),
                    INDEX idx_action (action),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // Insert default super admin
            $adminPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("INSERT IGNORE INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['Administrator', 'admin@jelocdw.com', $adminPassword, 'super_admin']);

            // Insert default settings
            $defaultSettings = [
                ['site_name', APP_NAME],
                ['site_email', 'info@jelocdw.com'],
                ['site_phone', ''],
                ['paystack_public_key', PAYSTACK_PUBLIC_KEY],
                ['paystack_secret_key', PAYSTACK_SECRET_KEY],
                ['currency', CURRENCY],
                ['currency_symbol', CURRENCY_SYMBOL],
            ];

            $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($defaultSettings as [$key, $value]) {
                $stmt->execute([$key, $value]);
            }

            // Mark setup as complete
            $markerFile = __DIR__ . '/.setup_complete';
            file_put_contents($markerFile, date('Y-m-d H:i:s'));

            $message = "Installation complete! You can now log in at <a href='admin/login.php'>Admin Panel</a>.<br>
                        <strong>Default credentials:</strong><br>
                        Email: admin@jelocdw.com<br>
                        Password: admin123<br><br>
                        <strong style='color:red;'>IMPORTANT: Delete this setup.php file for security!</strong>";

        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Check if already installed
$isInstalled = file_exists(__DIR__ . '/.setup_complete');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .setup-card { max-width: 600px; margin: 60px auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-card">
            <div class="card shadow">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0"><?= e(APP_NAME) ?> - Setup</h4>
                </div>
                <div class="card-body">
                    <?php if ($isInstalled && empty($message)): ?>
                        <div class="alert alert-warning">
                            <strong>Setup has already been completed.</strong><br>
                            For security, please delete this <code>setup.php</code> file.
                        </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>

                    <?php if (!$isInstalled || !empty($error)): ?>
                        <p>This will create the database and tables needed for <?= e(APP_NAME) ?>.</p>
                        <p><strong>Database:</strong> <?= e(DB_NAME) ?></p>
                        <p><strong>Host:</strong> <?= e(DB_HOST) ?></p>
                        <hr>
                        <form method="POST">
                            <input type="hidden" name="action" value="install">
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Install Database & Create Admin
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
