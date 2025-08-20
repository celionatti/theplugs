<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;

class key-generate extends Command
{
    protected string $description = 'Generate a random 32 or 64 char app key and save to .env';

    public function handle(): int
    {
        $length = (int)($this->option('l') ?? $this->option('length') ?? 32);
        if (!in_array($length, [32,64], true)) $length = 32;
        $key = bin2hex(random_bytes($length/2));

        $envPath = '.env';
        $env = file_exists($envPath) ? file_get_contents($envPath) : '';
        if (preg_match('/^APP_KEY=.*/m', (string)$env)) {
            $env = (string)preg_replace('/^APP_KEY=.*/m', 'APP_KEY='.$key, (string)$env);
        } else {
            $env .= (str_ends_with((string)$env, "\n") ? '' : "\n") . 'APP_KEY='.$key."\n";
        }
        file_put_contents($envPath, $env);
        $this->output->success("App key generated and saved to .env");
        return 0;
    }
}