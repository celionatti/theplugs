<?php

declare(strict_types=1);

namespace Plugs\Services;

use Plugs\Http\Router\Route;
use Plugs\Http\Router\Router;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;

class RoutingServiceProvider
{
    private Router $router;
    private ?object $container;

    public function __construct(?object $container = null)
    {
        $this->container = $container;
        $this->router = new Router($container);
        Route::setRouter($this->router);
    }

    public function boot(): void
    {
        // Register global middleware here if needed
        // $this->router->middleware(['cors', 'throttle']);
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Handle incoming request
     */
    public function handleRequest(?Request $request = null): Response
    {
        $request = $request ?: new Request();
        
        try {
            return $this->router->dispatch($request);
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    private function handleException(\Throwable $e, Request $request): Response
    {
        // In a real application, you'd have more sophisticated error handling
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getCode() : 500;
        
        if ($statusCode === 0) {
            $statusCode = 500;
        }

        return new Response([
            'error' => $e->getMessage(),
            'code' => $statusCode
        ], $statusCode);
    }
}