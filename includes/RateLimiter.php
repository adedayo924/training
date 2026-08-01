<?php
/**
 * Simple Rate Limiter
 *
 * Uses the database to track login attempts.
 */

class RateLimiter
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
        $this->ensureTable();
    }

    /**
     * Create the login_attempts table if it doesn't exist.
     */
    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email_ip (email, ip_address),
                INDEX idx_attempted_at (attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Check if the given email/IP has exceeded the max attempts.
     *
     * @param string $email
     * @param string $ip
     * @param int    $maxAttempts
     * @param int    $windowMinutes
     *
     * @return bool True if rate limit exceeded (should block)
     */
    public function isRateLimited(string $email, string $ip, int $maxAttempts = 5, int $windowMinutes = 15): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts
            WHERE email = ? AND ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        $stmt->execute([$email, $ip, $windowMinutes]);
        $row = $stmt->fetch();

        return ($row['attempts'] ?? 0) >= $maxAttempts;
    }

    /**
     * Record a failed login attempt.
     */
    public function recordAttempt(string $email, string $ip): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)
        ");
        $stmt->execute([$email, $ip]);
    }

    /**
     * Clear failed attempts on successful login.
     */
    public function clearAttempts(string $email, string $ip): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM login_attempts WHERE email = ? AND ip_address = ?
        ");
        $stmt->execute([$email, $ip]);
    }

    /**
     * Clean up old attempts (older than 24 hours).
     */
    public function cleanup(): void
    {
        $this->db->exec("
            DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
    }
}
