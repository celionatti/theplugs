<?php

declare(strict_types=1);

namespace Plugs\Database;

use PDO;
use PDOException;

class DatabaseConfig
{
    private static array $connections = [];
    private static array $config = [];

    public static function loadConfig(): void
    {
        // Load from .env file
        $envPath = __DIR__ . '/../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                [$key, $value] = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }

        self::$config = [
            'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'connections' => [
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => $_ENV['DB_HOST'] ?? 'localhost',
                    'port' => $_ENV['DB_PORT'] ?? '3306',
                    'database' => $_ENV['DB_DATABASE'] ?? 'test',
                    'username' => $_ENV['DB_USERNAME'] ?? 'root',
                    'password' => $_ENV['DB_PASSWORD'] ?? '',
                    'charset' => 'utf8mb4',
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_STRINGIFY_FETCHES => false,
                    ]
                ],
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => $_ENV['DB_DATABASE'] ?? ':memory:',
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                ],
                'pgsql' => [
                    'driver' => 'pgsql',
                    'host' => $_ENV['DB_HOST'] ?? 'localhost',
                    'port' => $_ENV['DB_PORT'] ?? '5432',
                    'database' => $_ENV['DB_DATABASE'] ?? 'postgres',
                    'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
                    'password' => $_ENV['DB_PASSWORD'] ?? '',
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                ]
            ]
        ];
    }

    public static function getConnection(string $name = null): PDO
    {
        $name = $name ?? self::$config['default'];

        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = self::createConnection($name);
        }

        return self::$connections[$name];
    }

    private static function createConnection(string $name): PDO
    {
        if (!isset(self::$config['connections'][$name])) {
            throw new PDOException("Database connection [{$name}] not configured.");
        }

        $config = self::$config['connections'][$name];

        try {
            $dsn = match ($config['driver']) {
                'mysql' => "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                'sqlite' => "sqlite:{$config['database']}",
                'pgsql' => "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
                default => throw new PDOException("Unsupported driver: {$config['driver']}")
            };

            return new PDO(
                $dsn,
                $config['username'] ?? null,
                $config['password'] ?? null,
                $config['options'] ?? []
            );
        } catch (PDOException $e) {
            throw new PDOException("Could not connect to database [{$name}]: " . $e->getMessage());
        }
    }

    public static function getConfig(): array
    {
        return self::$config;
    }
}

DatabaseConfig::loadConfig();