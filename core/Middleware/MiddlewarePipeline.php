<?php

declare(strict_types=1);

namespace Plugs\Middleware;

use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Container\Container;

class MiddlewarePipeline
{
    private array $middleware = [];
    private Container $container;

    public function __construct(array $middleware, Container $container)
    {
        $this->middleware = $middleware ?? [];
        $this->container = $container;
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
            // Use the container to resolve the middleware
            if ($this->container->has($middleware)) {
                return $this->container->get($middleware);
            }
            
            // If it's a short name like 'auth', try to resolve it from config
            $middlewareClass = $this->resolveMiddlewareClass($middleware);
            
            if (class_exists($middlewareClass)) {
                return $this->container->make($middlewareClass);
            }
            
            throw new \InvalidArgumentException("Middleware {$middleware} not found");
        }

        throw new \InvalidArgumentException('Invalid middleware type');
    }

    private function resolveMiddlewareClass(string $shortName): string
    {
        // Try to get middleware mapping from config
        try {
            $mapping = $this->container->get('config')->get('middleware', []);
            return $mapping[$shortName] ?? $shortName;
        } catch (\Exception $e) {
            // Fallback to a default namespace
            return 'App\\Middleware\\' . ucfirst($shortName) . 'Middleware';
        }
    }
}