<?php

declare(strict_types=1);

namespace Plugs\Session\Driver;

use Plugs\Session\Interface\SessionDriverInterface;

class DatabaseSessionDriver implements SessionDriverInterface
{
    private \PDO $connection;
    private string $table;

    public function __construct(array $config)
    {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
        $this->connection = new \PDO($dsn, $config['username'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        
        $this->table = $config['table'] ?? 'sessions';
        $this->createTable();
    }

    private function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id VARCHAR(40) PRIMARY KEY,
            payload LONGTEXT NOT NULL,
            last_activity INT NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            INDEX sessions_last_activity_index (last_activity)
        ) ENGINE=InnoDB";
        
        $this->connection->exec($sql);
    }

    public function read(string $sessionId): string
    {
        $stmt = $this->connection->prepare(
            "SELECT payload FROM {$this->table} WHERE id = ?"
        );
        $stmt->execute([$sessionId]);
        
        $result = $stmt->fetchColumn();
        return $result ?: '';
    }

    public function write(string $sessionId, string $data): bool
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO {$this->table} (id, payload, last_activity) VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE payload = VALUES(payload), last_activity = VALUES(last_activity)"
        );
        
        return $stmt->execute([$sessionId, $data, time()]);
    }

    public function destroy(string $sessionId): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$sessionId]);
    }

    public function gc(int $maxLifetime): bool
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM {$this->table} WHERE last_activity < ?"
        );
        return $stmt->execute([time() - $maxLifetime]);
    }

    public function exists(string $sessionId): bool
    {
        $stmt = $this->connection->prepare("SELECT 1 FROM {$this->table} WHERE id = ?");
        $stmt->execute([$sessionId]);
        return $stmt->fetchColumn() !== false;
    }
}