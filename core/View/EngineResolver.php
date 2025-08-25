<?php

declare(strict_types=1);

namespace Plugs\View;

use InvalidArgumentException;
use Plugs\View\Contracts\EngineInterface;

class EngineResolver
{
    protected array $resolvers = [];
    protected array $resolved = [];

    public function register(string $engine, callable $resolver): void
    {
        unset($this->resolved[$engine]);
        $this->resolvers[$engine] = $resolver;
    }

    public function resolve(string $engine): EngineInterface
    {
        if (isset($this->resolved[$engine])) {
            return $this->resolved[$engine];
        }

        if (!isset($this->resolvers[$engine])) {
            throw new InvalidArgumentException("Engine [{$engine}] not found.");
        }

        return $this->resolved[$engine] = call_user_func($this->resolvers[$engine]);
    }

    public function has(string $engine): bool
    {
        return isset($this->resolvers[$engine]);
    }

    public function getRegistered(): array
    {
        return array_keys($this->resolvers);
    }
}