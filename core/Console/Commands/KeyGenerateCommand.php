<?php
declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;

class KeyGenerateCommand extends Command
{
    protected string $description = 'Generate a random 32 or 64 char app key and save to .env';

    public function handle(): int
    {
        $this->output->header("Application Key Generator");
        
        // Get and validate length
        $length = (int)($this->option('l') ?? $this->option('length') ?? 32);
        $originalLength = $length;
        
        if (!in_array($length, [32, 64], true)) {
            $this->output->warning("Invalid key length: {$originalLength}. Using default length of 32 characters.");
            $length = 32;
        }

        $this->output->info("Generating {$length}-character application key...");
        
        // Generate key with spinner
        $key = '';
        $this->output->spinner("Generating cryptographically secure key", 1);
        $key = bin2hex(random_bytes($length / 2));
        
        // Display generated key info
        $this->output->box(
            "Key Length: {$length} characters\nKey Format: Hexadecimal\nEntropy: " . ($length * 4) . " bits",
            "Key Information",
            "info"
        );

        $envPath = '.env';
        
        // Check if .env file exists
        if (!file_exists($envPath)) {
            $this->output->note("No .env file found. Creating new .env file...");
            $env = '';
        } else {
            $this->output->info("Found existing .env file");
            $env = file_get_contents($envPath);
        }

        // Check for existing APP_KEY
        $hasExistingKey = preg_match('/^APP_KEY=.*/m', (string)$env);
        
        if ($hasExistingKey) {
            $this->output->warning("Existing APP_KEY found in .env file");
            
            if (!$this->askConfirmationWithFallback("Do you want to overwrite the existing APP_KEY?")) {
                $this->output->info("Operation cancelled. Existing key preserved.");
                return 0;
            }
            
            $this->output->info("Replacing existing APP_KEY...");
            $env = (string)preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . $key, (string)$env);
        } else {
            $this->output->info("Adding new APP_KEY to .env file...");
            $env .= (str_ends_with((string)$env, "\n") ? '' : "\n") . 'APP_KEY=' . $key . "\n";
        }

        // Save with progress simulation
        $this->output->progressBar(3, function($step) use ($envPath, $env, $key) {
            switch($step) {
                case 1:
                    // Backup existing file
                    if (file_exists($envPath)) {
                        copy($envPath, $envPath . '.backup');
                    }
                    break;
                case 2:
                    // Write new content
                    file_put_contents($envPath, $env);
                    break;
                case 3:
                    // Verify write
                    $verification = file_get_contents($envPath);
                    if (!str_contains($verification, $key)) {
                        throw new \RuntimeException('Failed to verify key was written correctly');
                    }
                    break;
            }
        }, "Saving to .env file");

        // Success message with key preview
        $this->output->success("Application key generated and saved successfully!");
        
        // Show key preview (first and last 8 characters for security)
        $keyPreview = substr($key, 0, 8) . str_repeat('*', max(0, $length - 16)) . substr($key, -8);
        $this->output->box(
            "Key Preview: {$keyPreview}\nSaved to: {$envPath}",
            "✅ Success",
            "success"
        );

        // Security reminder
        $this->output->note("Remember to keep your .env file secure and never commit it to version control!");
        
        // Cleanup backup if everything went well
        if (file_exists($envPath . '.backup')) {
            unlink($envPath . '.backup');
            $this->output->debug("Cleaned up backup file");
        }

        return 0;
    }

    /**
     * Fallback for confirmation when interactive input isn't available
     */
    private function askConfirmationWithFallback(string $question): bool
    {
        try {
            return $this->output->askConfirmation($question);
        } catch (\Throwable $e) {
            // If interactive input fails, default to yes for overwrite
            $this->output->warning("Unable to get user input. Proceeding with overwrite...");
            return true;
        }
    }
}