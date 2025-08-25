<?php

declare(strict_types=1);

namespace Plugs\Middleware;

use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;

class pipeline
{
    private array $middleware = [];

    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    public function handle(Request $request, callable $destination): Response
    {
        return $this->createPipeline()($request, $destination);
    }

    private function createPipeline(): callable
    {
        return array_reduce(
            array_reverse($this->middleware),
            fn($next, $middleware) => fn(Request $request, callable $destination) => 
                $this->resolveMiddleware($middleware)->handle($request, 
                    fn(Request $req) => $next($req, $destination)
                ),
            fn(Request $request, callable $destination) => $destination($request)
        );
    }

    private function resolveMiddleware(string|MiddlewareInterface $middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if (is_string($middleware)) {
            return new $middleware();
        }

        throw new \InvalidArgumentException('Invalid middleware type');
    }
}