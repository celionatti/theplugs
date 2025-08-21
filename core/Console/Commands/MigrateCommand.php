<?php
declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Database\DB;

class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run the database migrations';

    public function handle(): int
    {
        $this->output->header("Database Migration");
        
        try {
            // Ensure migrations table exists
            $this->createMigrationsTable();
            
            // Get pending migrations
            $pendingMigrations = $this->getPendingMigrations();
            
            if (empty($pendingMigrations)) {
                $this->output->success("Database is up to date - no pending migrations.");
                return 0;
            }

            // Show what will be migrated
            $this->displayPendingMigrations($pendingMigrations);
            
            // Get next batch number
            $batch = $this->getNextBatchNumber();
            
            $this->output->info("Running migrations in batch #{$batch}");

            // Run migrations
            $this->runMigrations($pendingMigrations, $batch);

            $this->output->box(
                "Successfully executed " . count($pendingMigrations) . " migration(s) in batch #{$batch}",
                "✅ Migration Complete",
                "success"
            );

            return 0;

        } catch (\Exception $e) {
            $this->output->critical("Migration failed: " . $e->getMessage());
            $this->output->debug("Error details: " . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Create the migrations table if it doesn't exist
     */
    private function createMigrationsTable(): void
    {
        $this->output->info("Checking migrations table...");
        
        $createTableSql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        )";

        try {
            DB::unprepared($createTableSql);
            $this->output->success("✓ Migrations table ready");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to create migrations table: " . $e->getMessage());
        }
    }

    /**
     * Get all pending migrations
     */
    private function getPendingMigrations(): array
    {
        // Get already run migrations
        $ranMigrations = [];
        try {
            $results = DB::select("SELECT migration FROM migrations");
            $ranMigrations = array_column($results, 'migration');
        } catch (\Exception $e) {
            $this->output->debug("Could not read migrations table (this is normal for first run)");
        }

        // Get all migration files
        $migrationFiles = glob('database/migrations/*.php');
        
        if ($migrationFiles === false) {
            throw new \RuntimeException("Could not read migration files from database/migrations/");
        }

        // Filter out already run migrations
        $pendingMigrations = [];
        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            if (!in_array($filename, $ranMigrations, true)) {
                $pendingMigrations[] = [
                    'file' => $file,
                    'name' => $filename
                ];
            }
        }

        // Sort by filename to ensure proper order
        usort($pendingMigrations, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $pendingMigrations;
    }

    /**
     * Display pending migrations in a table
     */
    private function displayPendingMigrations(array $migrations): void
    {
        $this->output->subHeader("Pending Migrations");
        
        $tableData = [];
        foreach ($migrations as $index => $migration) {
            $tableData[] = [
                'Order' => $index + 1,
                'Migration' => $migration['name']
            ];
        }
        
        $this->output->table(['Order', 'Migration'], $tableData);
    }

    /**
     * Get the next batch number
     */
    private function getNextBatchNumber(): int
    {
        try {
            $result = DB::selectOne("SELECT MAX(batch) as max_batch FROM migrations");
            return (int)($result['max_batch'] ?? 0) + 1;
        } catch (\Exception $e) {
            return 1; // First batch
        }
    }

    /**
     * Run all pending migrations
     */
    private function runMigrations(array $migrations, int $batch): void
    {
        $successful = 0;
        $failed = 0;

        $this->output->progressBar(count($migrations), function($step) use ($migrations, $batch, &$successful, &$failed) {
            $migration = $migrations[$step - 1];
            
            try {
                $this->runSingleMigration($migration, $batch);
                $successful++;
            } catch (\Exception $e) {
                $failed++;
                $this->output->error("Failed to run {$migration['name']}: " . $e->getMessage());
                
                // Stop on first failure to maintain database integrity
                throw new \RuntimeException("Migration failed, stopping execution to prevent inconsistent state");
            }
        }, "Running migrations");

        // Summary
        if ($successful > 0) {
            $this->output->success("Successfully executed {$successful} migration(s)");
        }
        
        if ($failed > 0) {
            $this->output->error("Failed to execute {$failed} migration(s)");
        }
    }

    /**
     * Run a single migration
     */
    private function runSingleMigration(array $migration, int $batch): void
    {
        if (!file_exists($migration['file'])) {
            throw new \RuntimeException("Migration file not found: {$migration['name']}");
        }

        // Use transaction for safety
        DB::transaction(function() use ($migration, $batch) {
            // Load and execute the migration
            $instance = require $migration['file'];
            
            if (!is_object($instance) || !method_exists($instance, 'up')) {
                throw new \RuntimeException("Invalid migration file: {$migration['name']} - missing up() method");
            }

            // Execute the up method
            $instance->up();

            // Record in migrations table
            DB::insert(
                "INSERT INTO migrations (migration, batch) VALUES (?, ?)",
                [$migration['name'], $batch]
            );
        });

        $this->output->success("✓ Migrated: {$migration['name']}");
    }

    /**
     * Validate migration directory exists
     */
    private function validateMigrationDirectory(): void
    {
        $migrationDir = 'database/migrations';
        
        if (!is_dir($migrationDir)) {
            throw new \RuntimeException(
                "Migration directory not found: {$migrationDir}\n" .
                "Please create the directory and add your migration files."
            );
        }

        if (!is_readable($migrationDir)) {
            throw new \RuntimeException("Migration directory is not readable: {$migrationDir}");
        }
    }
}