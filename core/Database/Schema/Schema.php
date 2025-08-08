<?php

declare(strict_types=1);

namespace Plugs\Database\Schema;

use PDO;
use Closure;
use Plugs\Database\DatabaseConfig;
use Plugs\Database\Schema\Blueprint;

class Schema
{
    private static ?PDO $connection = null;

    public static function connection(?string $name = null): void
    {
        self::$connection = DatabaseConfig::getConnection($name);
    }

    public static function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table, 'create');
        $callback($blueprint);
        
        $sql = $blueprint->toSql(self::getConnection());
        self::getConnection()->exec($sql);
    }

    public static function table(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table, 'alter');
        $callback($blueprint);
        
        $sql = $blueprint->toSql(self::getConnection());
        self::getConnection()->exec($sql);
    }

    public static function drop(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS {$table}";
        self::getConnection()->exec($sql);
    }

    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }

    public static function rename(string $from, string $to): void
    {
        $sql = "ALTER TABLE {$from} RENAME TO {$to}";
        self::getConnection()->exec($sql);
    }

    public static function hasTable(string $table): bool
    {
        $driver = self::getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        $sql = match ($driver) {
            'mysql' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
            'sqlite' => "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = ?",
            'pgsql' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?",
            default => throw new \RuntimeException("Unsupported driver: {$driver}")
        };

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$table]);
        
        return (bool) $stmt->fetchColumn();
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $driver = self::getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        $sql = match ($driver) {
            'mysql' => "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            'sqlite' => "PRAGMA table_info({$table})",
            'pgsql' => "SELECT COUNT(*) FROM information_schema.columns WHERE table_name = ? AND column_name = ?",
            default => throw new \RuntimeException("Unsupported driver: {$driver}")
        };

        if ($driver === 'sqlite') {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute();
            $columns = $stmt->fetchAll();
            return in_array($column, array_column($columns, 'name'));
        }

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$table, $column]);
        
        return (bool) $stmt->fetchColumn();
    }

    private static function getConnection(): PDO
    {
        return self::$connection ?? DatabaseConfig::getConnection();
    }
}