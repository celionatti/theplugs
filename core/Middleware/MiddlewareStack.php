<?php

declare(strict_types=1);

namespace Plugs\Middleware;

class MiddlewareStack
{
    private array $middleware = [];
    private array $parameters = [];

    public function add(string $middleware, array $parameters = []): static
    {
        $this->middleware[] = $middleware;
        $this->parameters[$middleware] = $parameters;
        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getParameters(string $middleware): array
    {
        return $this->parameters[$middleware] ?? [];
    }
}