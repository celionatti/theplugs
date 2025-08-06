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

    public function __construct()
    {
        // Initialize the router
        $this->router = new Router();
        
        // Set it in the Route facade
        Route::setRouter($this->router);
    }

    public function boot(): void
    {
        // Can register global middleware here if needed
        // $this->router->middleware(['web']);
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
            // Simple error response
            return new Response([
                'error' => $e->getMessage(),
                'code' => $e->getCode() ?: 500
            ], $e->getCode() ?: 500);
        }
    }
}