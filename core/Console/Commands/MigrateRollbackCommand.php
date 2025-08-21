<?php
declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Database\DB;

class MigrateRollbackCommand extends Command
{
    protected string $name = 'migrate:rollback';
    protected string $description = 'Rollback the latest batch of migrations';

    public function handle(): int
    {
        $this->output->header("Migration Rollback");
        
        try {
            // Get the current batch number
            $batch = $this->getCurrentBatch();
            
            if ($batch === 0) {
                $this->output->warning("Nothing to rollback - no migrations found.");
                return 0;
            }

            $this->output->info("Found migrations in batch #{$batch}");

            // Get migrations to rollback
            $migrations = $this->getMigrationsToRollback($batch);
            
            if (empty($migrations)) {
                $this->output->warning("No migrations found in current batch.");
                return 0;
            }

            // Show what will be rolled back
            $this->displayMigrationsToRollback($migrations);

            // Ask for confirmation
            if (!$this->askConfirmationWithFallback("Are you sure you want to rollback these migrations?")) {
                $this->output->info("Rollback cancelled.");
                return 0;
            }

            // Perform rollback
            $this->performRollback($migrations);

            $this->output->box(
                "Successfully rolled back " . count($migrations) . " migration(s) from batch #{$batch}",
                "✅ Rollback Complete",
                "success"
            );

            return 0;

        } catch (\Exception $e) {
            $this->output->critical("Rollback failed: " . $e->getMessage());
            $this->output->debug("Error details: " . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Get the current batch number
     */
    private function getCurrentBatch(): int
    {
        try {
            $result = DB::selectOne("SELECT MAX(batch) as max_batch FROM migrations");
            return (int)($result['max_batch'] ?? 0);
        } catch (\Exception $e) {
            $this->output->warning("Could not read migrations table. Have you run migrations?");
            return 0;
        }
    }

    /**
     * Get migrations to rollback for the given batch
     */
    private function getMigrationsToRollback(int $batch): array
    {
        return DB::select(
            "SELECT * FROM migrations WHERE batch = ? ORDER BY id DESC",
            [$batch]
        );
    }

    /**
     * Display the migrations that will be rolled back
     */
    private function displayMigrationsToRollback(array $migrations): void
    {
        $this->output->subHeader("Migrations to Rollback");
        
        $tableData = [];
        foreach ($migrations as $migration) {
            $tableData[] = [
                'ID' => $migration['id'],
                'Migration' => $migration['migration'],
                'Batch' => $migration['batch']
            ];
        }
        
        $this->output->table(['ID', 'Migration', 'Batch'], $tableData);
    }

    /**
     * Perform the actual rollback
     */
    private function performRollback(array $migrations): void
    {
        $successful = 0;
        $failed = 0;

        $this->output->progressBar(count($migrations), function($step) use ($migrations, &$successful, &$failed) {
            $migration = $migrations[$step - 1];
            
            try {
                $this->rollbackSingleMigration($migration);
                $successful++;
            } catch (\Exception $e) {
                $failed++;
                $this->output->error("Failed to rollback {$migration['migration']}: " . $e->getMessage());
            }
        }, "Rolling back migrations");

        // Summary
        if ($successful > 0) {
            $this->output->success("Successfully rolled back {$successful} migration(s)");
        }
        
        if ($failed > 0) {
            $this->output->error("Failed to rollback {$failed} migration(s)");
        }
    }

    /**
     * Rollback a single migration
     */
    private function rollbackSingleMigration(array $migration): void
    {
        $migrationFile = "database/migrations/{$migration['migration']}";
        
        if (!file_exists($migrationFile)) {
            throw new \RuntimeException("Migration file not found: {$migration['migration']}");
        }

        // Use transaction for safety
        DB::transaction(function() use ($migrationFile, $migration) {
            // Load and execute the migration down method
            $instance = require $migrationFile;
            
            if (!is_object($instance) || !method_exists($instance, 'down')) {
                throw new \RuntimeException("Invalid migration file: {$migration['migration']} - missing down() method");
            }

            // Execute the down method
            $instance->down();

            // Remove from migrations table
            DB::delete("DELETE FROM migrations WHERE id = ?", [$migration['id']]);
        });

        $this->output->success("✓ Rolled back: {$migration['migration']}");
    }

    /**
     * Fallback for confirmation when interactive input isn't available
     */
    private function askConfirmationWithFallback(string $question): bool
    {
        try {
            return $this->output->askConfirmation($question);
        } catch (\Throwable $e) {
            // If interactive input fails, default to no for safety
            $this->output->warning("Unable to get user input. Aborting for safety...");
            return false;
        }
    }
}