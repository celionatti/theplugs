<?php

declare(strict_types=1);

namespace Plugs\Console;

use Plugs\Console\Commands\HelpCommand;
use Plugs\Console\Commands\DbSeedCommand;
use Plugs\Console\Commands\MakeModelCommand;
use Plugs\Console\Commands\RouteListCommand;
use Plugs\Console\Commands\CacheClearCommand;
use Plugs\Console\Commands\ConfigCacheCommand;
use Plugs\Console\Commands\KeyGenerateCommand;
use Plugs\Console\Commands\MakeCommandCommand;
use Plugs\Console\Commands\MakeFrameworkCommand;
use Plugs\Console\Commands\MakeMigrationCommand;
use Plugs\Console\Commands\MakeControllerCommand;

class ConsoleKernel
{
    /** @var array<string, string> */
    protected array $commands = [
        'help'            => HelpCommand::class,
        'make:framework'  => MakeFrameworkCommand::class,
        'make:controller' => MakeControllerCommand::class,
        'make:model'      => MakeModelCommand::class,
        'make:migration'  => MakeMigrationCommand::class,
        'make:command'    => MakeCommandCommand::class,
        'key:generate'    => KeyGenerateCommand::class,
        'cache:clear'     => CacheClearCommand::class,
        'config:cache'    => ConfigCacheCommand::class,
        'route:list'      => RouteListCommand::class,
        'db:seed'         => DbSeedCommand::class,
    ];

    /** @var array<string, string> */
    protected array $aliases = [
        'g:c' => 'make:controller',
        'g:m' => 'make:model',
        'g:cmd' => 'make:command',
    ];

    public function commands(): array { return $this->commands; }

    public function aliases(): array { return $this->aliases; }

    public function register(string $name, string $class): void
    {
        $this->commands[$name] = $class;
    }

    public function resolve(string $name): ?Command
    {
        $lookup = $this->aliases[$name] ?? $name;
        $class  = $this->commands[$lookup] ?? null;
        if (!$class) return null;
        return new $class($lookup);
    }
}