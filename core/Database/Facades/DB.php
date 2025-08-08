<?php

declare(strict_types=1);

namespace Plugs\Database;

use PDO;
use Plugs\Database\QueryBuilder;
use Plugs\Database\Schema\Schema;
use Plugs\Database\DatabaseConfig;

class DB
{
    private static ?PDO $connection = null;
    private static ?string $connectionName = null;

    public static function connection(?string $name = null): PDO
    {
        if ($name !== null) {
            self::$connectionName = $name;
            self::$connection = DatabaseConfig::getConnection($name);
        }

        return self::$connection ?? DatabaseConfig::getConnection();
    }

    public static function table(string $table): QueryBuilder
    {
        $builder = new QueryBuilder(self::connection());
        return $builder->table($table);
    }

    // Raw query methods
    public static function select(string $query, array $bindings = []): array
    {
        $stmt = self::connection()->prepare($query);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    public static function selectOne(string $query, array $bindings = []): ?array
    {
        $results = self::select($query, $bindings);
        return $results[0] ?? null;
    }

    public static function insert(string $query, array $bindings = []): bool
    {
        $stmt = self::connection()->prepare($query);
        return $stmt->execute($bindings);
    }

    public static function update(string $query, array $bindings = []): int
    {
        $stmt = self::connection()->prepare($query);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    public static function delete(string $query, array $bindings = []): int
    {
        return self::update($query, $bindings);
    }

    public static function statement(string $query, array $bindings = []): bool
    {
        $stmt = self::connection()->prepare($query);
        return $stmt->execute($bindings);
    }

    public static function unprepared(string $query): bool
    {
        return self::connection()->exec($query) !== false;
    }

    // Transaction methods
    public static function transaction(callable $callback)
    {
        self::beginTransaction();

        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollBack();
            throw $e;
        }
    }

    public static function beginTransaction(): void
    {
        self::connection()->beginTransaction();
    }

    public static function commit(): void
    {
        self::connection()->commit();
    }

    public static function rollBack(): void
    {
        self::connection()->rollBack();
    }

    public static function inTransaction(): bool
    {
        return self::connection()->inTransaction();
    }

    // Utility methods
    public static function lastInsertId(): string
    {
        return self::connection()->lastInsertId();
    }

    public static function getDatabaseName(): string
    {
        $config = DatabaseConfig::getConfig();
        $connectionName = self::$connectionName ?? $config['default'];
        return $config['connections'][$connectionName]['database'];
    }

    public static function getTablePrefix(): string
    {
        $config = DatabaseConfig::getConfig();
        $connectionName = self::$connectionName ?? $config['default'];
        return $config['connections'][$connectionName]['prefix'] ?? '';
    }

    // Schema builder shortcut
    public static function schema(): Schema
    {
        return new Schema();
    }

    // Logging and debugging
    public static function enableQueryLog(): void
    {
        // Implementation would depend on logging requirements
        self::connection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function disableQueryLog(): void
    {
        // Implementation would depend on logging requirements
    }

    public static function getQueryLog(): array
    {
        // Return logged queries - implementation would depend on logging system
        return [];
    }

    public static function flushQueryLog(): void
    {
        // Clear query log - implementation would depend on logging system
    }

    // Connection info
    public static function getDriverName(): string
    {
        return self::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function getServerVersion(): string
    {
        return self::connection()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    // Proxy common query builder methods
    public static function __callStatic(string $method, array $parameters)
    {
        // Allow direct DB::where(), DB::select(), etc.
        $builder = new QueryBuilder(self::connection());
        
        if (method_exists($builder, $method)) {
            return $builder->$method(...$parameters);
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist on QueryBuilder");
    }
}