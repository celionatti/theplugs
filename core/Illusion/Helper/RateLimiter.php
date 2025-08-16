<?php

declare(strict_types=1);

namespace Plugs\Illusion\Helper;

use PDO;
use Plugs\Authentication\AuthConfig;

class RateLimiter
{
    private PDO $db;
    private AuthConfig $config;

    public function __construct(PDO $db, AuthConfig $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function attemptLogin(string $email, string $ipAddress): bool
    {
        $this->cleanOldAttempts();
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM login_attempts 
            WHERE (email = ? OR ip_address = ?) 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$email, $ipAddress]);
        $attempts = (int)$stmt->fetchColumn();

        return $attempts < $this->config->maxLoginAttempts;
    }

    public function recordFailedAttempt(string $email, string $ipAddress): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (email, ip_address, attempted_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$email, $ipAddress]);
    }

    public function clearFailedAttempts(string $email): void
    {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
    }

    private function cleanOldAttempts(): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM login_attempts 
            WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
    }
}
