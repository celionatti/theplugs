<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;

class MigrateRollbackCommand extends Command
{
    protected string $name = 'migrate:rollback';
    protected string $description = 'Rollback the latest batch of migrations';

    public function handle(): void
    {
        $this->output->header("Plugs Console - Rollback");

        $db = DatabaseManager::connection();
        $batch = (int) $db->query("SELECT MAX(batch) FROM migrations")->fetchColumn();

        if ($batch === 0) {
            $this->output->warning("Nothing to rollback.");
            return;
        }

        $stmt = $db->prepare("SELECT * FROM migrations WHERE batch = :b ORDER BY id DESC");
        $stmt->execute(['b' => $batch]);
        $migrations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($migrations as $migration) {
            $file = "database/migrations/{$migration['migration']}";

            if (!file_exists($file)) {
                $this->output->error("Missing file: {$migration['migration']}");
                continue;
            }

            $this->output->spinner("Rolling back: {$migration['migration']}", function () use ($file, $db, $migration) {
                $instance = require $file;
                $instance->down();

                $stmt = $db->prepare("DELETE FROM migrations WHERE id = :id");
                $stmt->execute(['id' => $migration['id']]);
            });

            $this->output->success("Rolled back: {$migration['migration']}");
        }

        $this->output->info("Rollback completed.");
    }
}