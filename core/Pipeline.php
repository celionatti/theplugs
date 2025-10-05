<?php

declare(strict_types=1);

namespace Plugs;

use Closure;
use Plugs\Container\Container;
use Plugs\Http\Request\Request;
use Plugs\Middleware\MiddlewareInterface;

class Pipeline
{
    protected Container $container;
    protected $passable;
    protected array $pipes = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     */
    public function send($passable): self
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Set the array of pipes.
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                // Resolve the middleware instance
                $middleware = $this->resolveMiddleware($pipe);

                // Call the middleware's handle method
                return $middleware->handle($passable, $stack);
            };
        };
    }

    /**
     * Resolve middleware from string class name.
     */
    protected function resolveMiddleware($pipe): MiddlewareInterface
    {
        if ($pipe instanceof MiddlewareInterface) {
            return $pipe;
        }

        if (is_string($pipe)) {
            // Parse middleware with parameters (e.g., 'throttle:60,1')
            [$class, $parameters] = $this->parseMiddlewareParameters($pipe);

            // Resolve from container
            $instance = $this->container->get($class);

            // Set parameters if the middleware supports them
            if (!empty($parameters) && method_exists($instance, 'setParameters')) {
                $instance->setParameters($parameters);
            }

            if (!$instance instanceof MiddlewareInterface) {
                throw new \RuntimeException(
                    "Middleware {$class} must implement MiddlewareInterface"
                );
            }

            return $instance;
        }

        throw new \InvalidArgumentException('Invalid middleware type');
    }

    /**
     * Parse middleware parameters from string.
     */
    protected function parseMiddlewareParameters(string $middleware): array
    {
        if (strpos($middleware, ':') === false) {
            return [$middleware, []];
        }

        [$class, $parameterString] = explode(':', $middleware, 2);
        $parameters = explode(',', $parameterString);

        return [$class, array_map('trim', $parameters)];
    }

    /**
     * Get the final piece of the Closure onion.
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }
}
