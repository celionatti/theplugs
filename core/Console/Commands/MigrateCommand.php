<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;

class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run the database migrations';

    public function handle(): void
    {
        $this->output->header("Plugs Console - Migrate");

        $db = DatabaseManager::connection();
        $db->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $ran = $db->query("SELECT migration FROM migrations")->fetchAll(\PDO::FETCH_COLUMN);
        $files = glob('database/migrations/*.php');
        $batch = (int) $db->query("SELECT MAX(batch) FROM migrations")->fetchColumn() + 1;

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $ran, true)) {
                continue;
            }

            $this->output->spinner("Migrating: {$name}", function () use ($file, $db, $name, $batch) {
                $migration = require $file;
                $migration->up();

                $stmt = $db->prepare("INSERT INTO migrations (migration, batch) VALUES (:m, :b)");
                $stmt->execute(['m' => $name, 'b' => $batch]);
            });

            $this->output->success("Migrated: {$name}");
        }

        $this->output->info("Migrations completed.");
    }
}