<?php

declare(strict_types=1);

namespace Plugs\Database\Migrations;

use PDO;
use DirectoryIterator;
use Plugs\Database\DatabaseConfig;
use Plugs\Database\Migrations\Migration;

class MigrationRunner
{
    private PDO $connection;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct(string $migrationsPath = 'database/migrations')
    {
        $this->connection = DatabaseConfig::getConnection();
        $this->migrationsPath = $migrationsPath;
        $this->ensureMigrationsTableExists();
    }

    public function run(): void
    {
        $this->info("Running migrations...");

        $migrations = $this->getPendingMigrations();

        if (empty($migrations)) {
            $this->info("Nothing to migrate.");
            return;
        }

        foreach ($migrations as $migration) {
            $this->runMigration($migration);
        }

        $this->info("Migrations completed successfully.");
    }

    public function rollback(int $steps = 1): void
    {
        $this->info("Rolling back migrations...");

        $migrations = $this->getExecutedMigrations($steps);

        if (empty($migrations)) {
            $this->info("Nothing to rollback.");
            return;
        }

        foreach ($migrations as $migration) {
            $this->rollbackMigration($migration);
        }

        $this->info("Rollback completed successfully.");
    }

    public function reset(): void
    {
        $this->info("Resetting all migrations...");

        $migrations = $this->getExecutedMigrations();

        foreach (array_reverse($migrations) as $migration) {
            $this->rollbackMigration($migration);
        }

        $this->info("All migrations reset successfully.");
    }

    public function refresh(): void
    {
        $this->info("Refreshing migrations...");
        $this->reset();
        $this->run();
        $this->info("Migrations refreshed successfully.");
    }

    public function status(): void
    {
        $this->info("Migration Status:");
        $this->info(str_repeat('-', 50));

        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrationNames();

        foreach ($allMigrations as $migration) {
            $name = $this->getMigrationName($migration);
            $status = in_array($name, $executedMigrations) ? 'Ran' : 'Pending';
            $this->info(sprintf("%-40s %s", $name, $status));
        }
    }

    public function make(string $name, ?string $table = null, bool $create = false): void
    {
        $className = $this->getClassName($name);
        $filename = $this->getFilename($name);
        $filepath = $this->migrationsPath . '/' . $filename;

        if (file_exists($filepath)) {
            $this->error("Migration already exists: {$filename}");
            return;
        }

        $stub = $create ? $this->getCreateTableStub($className, $table) : $this->getStub($className, $table);

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        file_put_contents($filepath, $stub);
        $this->info("Created migration: {$filename}");
    }

    private function getPendingMigrations(): array
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrationNames();

        $pending = [];
        foreach ($allMigrations as $migration) {
            $name = $this->getMigrationName($migration);
            if (!in_array($name, $executedMigrations)) {
                $pending[] = $migration;
            }
        }

        return $pending;
    }

    private function getExecutedMigrations(?int $limit = null): array
    {
        $sql = "SELECT migration FROM {$this->migrationsTable} ORDER BY batch DESC, id DESC";

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        return array_column($stmt->fetchAll(), 'migration');
    }

    private function getExecutedMigrationNames(): array
    {
        return $this->getExecutedMigrations();
    }

    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = [];
        $iterator = new DirectoryIterator($this->migrationsPath);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getFilename();
            }
        }

        sort($files);
        return $files;
    }

    private function runMigration(string $filename): void
    {
        $this->info("Migrating: {$filename}");

        $migration = $this->resolveMigration($filename);

        try {
            $migration->up();
            $this->recordMigration($filename);
            $this->info("Migrated: {$filename}");
        } catch (\Exception $e) {
            $this->error("Migration failed: {$filename}");
            $this->error("Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function rollbackMigration(string $migrationName): void
    {
        $filename = $this->findMigrationFile($migrationName);

        if (!$filename) {
            $this->error("Migration file not found: {$migrationName}");
            return;
        }

        $this->info("Rolling back: {$filename}");

        $migration = $this->resolveMigration($filename);

        try {
            $migration->down();
            $this->removeMigrationRecord($migrationName);
            $this->info("Rolled back: {$filename}");
        } catch (\Exception $e) {
            $this->error("Rollback failed: {$filename}");
            $this->error("Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function resolveMigration(string $filename): Migration
    {
        require_once $this->migrationsPath . '/' . $filename;

        $className = $this->getClassNameFromFile($filename);

        if (!class_exists($className)) {
            throw new \Exception("Migration class not found: {$className}");
        }

        return new $className();
    }

    private function recordMigration(string $filename): void
    {
        $migrationName = $this->getMigrationName($filename);
        $batch = $this->getNextBatchNumber();

        $sql = "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$migrationName, $batch]);
    }

    private function removeMigrationRecord(string $migrationName): void
    {
        $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$migrationName]);
    }

    private function getNextBatchNumber(): int
    {
        $sql = "SELECT MAX(batch) FROM {$this->migrationsTable}";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        $maxBatch = $stmt->fetchColumn();
        return $maxBatch ? $maxBatch + 1 : 1;
    }

    private function getMigrationName(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    private function getClassNameFromFile(string $filename): string
    {
        $name = $this->getMigrationName($filename);
        // Remove timestamp prefix
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $name);
        // Convert to PascalCase
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    private function findMigrationFile(string $migrationName): ?string
    {
        $files = $this->getAllMigrationFiles();

        foreach ($files as $file) {
            if ($this->getMigrationName($file) === $migrationName) {
                return $file;
            }
        }

        return null;
    }

    private function getClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    private function getFilename(string $name): string
    {
        $timestamp = date('Y_m_d_His');
        return "{$timestamp}_{$name}.php";
    }

    private function ensureMigrationsTableExists(): void
    {
        $sql = match ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            'mysql' => "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL
            )",
            'sqlite' => "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL,
                batch INTEGER NOT NULL
            )",
            'pgsql' => "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL
            )",
            default => throw new \RuntimeException('Unsupported database driver')
        };

        $this->connection->exec($sql);
    }

    private function getStub(string $className, ?string $table = null): string
    {
        $stub = <<<PHP
        <?php

        use Database\Migrations\Migration;
        use PDO;

        use DirectoryIterator;

        use PDO;

        use Database\Schema\Schema;
        use Database\Schema\Blueprint;

        class {$className} extends Migration
        {
            public function up(): void
            {
                Schema::table('{$table}', function (Blueprint \$table) {
                    //
                });
            }

            public function down(): void
            {
                Schema::table('{$table}', function (Blueprint \$table) {
                    //
                });
            }
        }
        PHP;

        return $stub;
    }

    private function getCreateTableStub(string $className, string $table): string
    {
        $stub = <<<PHP
        <?php

        use Database\Migrations\Migration;
        use Database\Schema\Schema;
        use Database\Schema\Blueprint;

        class {$className} extends Migration
        {
            public function up(): void
            {
                Schema::create('{$table}', function (Blueprint \$table) {
                    \$table->id();
                    \$table->timestamps();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('{$table}');
            }
        }
        PHP;

        return $stub;
    }

    private function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    private function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }
}
