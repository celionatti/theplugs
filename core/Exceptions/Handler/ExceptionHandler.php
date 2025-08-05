<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Handler;

use Throwable;
use Plugs\Plugs;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;

class ExceptionHandler
{
    /**
     * The application instance.
     */
    protected Plugs $app;

    /**
     * Create a new exception handler instance.
     */
    public function __construct(Plugs $app)
    {
        $this->app = $app;
    }

    /**
     * Render an exception as an HTTP response.
     */
    public function render(Request $request, Throwable $exception): Response
    {
        if ($this->app->isEnvironment('local', 'testing')) {
            return $this->renderDebugResponse($exception);
        }

        return $this->renderProductionResponse($exception);
    }

    /**
     * Render a debug response for development.
     */
    protected function renderDebugResponse(Throwable $exception): Response
    {
        $content = [
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        return new Response(json_encode($content, JSON_PRETTY_PRINT), 500, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Render a production response.
     */
    protected function renderProductionResponse(Throwable $exception): Response
    {
        return new Response('Internal Server Error', 500);
    }

    /**
     * Report the exception to logging services.
     */
    public function report(Throwable $exception): void
    {
        // Log the exception
        error_log($exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    }
}
