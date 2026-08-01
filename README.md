# Jeilo CDW

Events and training registration website built with PHP, MySQL, and Bootstrap 5.

## Features

- **Public site**: Homepage with hero/stats, events listing with pagination, event detail with inline registration form
- **Admin panel**: Dashboard with stats, events CRUD, registration management with CSV export, admin user management, site settings
- **Payments**: Paystack integration (NGN), idempotent payment callback
- **Notifications**: HTML confirmation emails via `mail()`, WhatsApp share link generator
- **Security**: Prepared statements, CSRF protection, bcrypt passwords, session security headers, rate limiting, audit logging

## Requirements

- PHP 8.1+
- MySQL 5.7+
- Paystack account (live keys)

## Setup

1. Clone into your web root (e.g. `C:\wamp64\www\training`)
2. Copy `.env.example` to `.env` and fill in your Paystack keys and DB credentials
3. Visit `/setup.php` in your browser to create the database and default admin
4. Log in at `/admin/login.php` with `admin@jelocdw.com` / `admin123`
5. **Change the default password immediately**
6. Delete `setup.php` after setup

### WAMP (local development)

```
APP_ENV=development
APP_DEBUG=true
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=jeilo_cdw
DB_USER=root
DB_PASS=
```

### InfinityFree / production

- Upload all files via FTP
- Create MySQL database via control panel
- Update `.env` with production credentials
- Set `APP_ENV=production` and `APP_DEBUG=false`
- Run the `audit_log` table migration (see below)

## Audit Log Migration

For existing installs that were created before the audit_log feature:

```sql
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Project Structure

```
training/
├── admin/              # Admin panel
│   ├── includes/       # Admin header, footer, sidebar
│   ├── events.php      # Events list
│   ├── event-form.php  # Create/edit event
│   ├── event-delete.php
│   ├── registrations.php
│   ├── registration-detail.php
│   ├── export-csv.php
│   ├── settings.php
│   ├── admins.php
│   ├── admin-form.php
│   ├── admin-delete.php
│   ├── login.php
│   └── logout.php
├── assets/             # CSS, JS, images
├── config/             # Dotenv parser, config.php, database.php
├── includes/           # Shared functions, auth, email, WhatsApp
├── logs/               # error.log (web-blocked via .htaccess)
├── uploads/events/     # Event images (PHP execution blocked)
├── .env                # Secrets (not committed)
├── index.php           # Homepage
├── events.php          # Public events listing
├── event-detail.php    # Event detail + registration form
├── register-handler.php
├── payment-callback.php
├── registration-success.php
└── setup.php           # Installer (delete after use)
```

## Security Notes

- `.htaccess` blocks direct access to `.env`, `config/`, `logs/`, and `setup.php`
- `uploads/events/.htaccess` denies PHP execution in the uploads directory
- Event and admin deletes use POST forms (not GET links)
- Session cookies use `httponly`, `samesite=Lax`, and `strict_mode`
- Registration rate limiting: 5 attempts per 15 minutes per email
- CSRF tokens on all forms and state-changing requests
- All user input escaped with `e()` (htmlspecialchars) at output
- Flash messages escaped at storage time

## Default Credentials

- **Email**: `admin@jelocdw.com`
- **Password**: `admin123`
