<?php

declare(strict_types=1);

namespace Plugs;

use Closure;
use Plugs\Container\Container;

class Pipeline
{
    /**
     * The object being passed through the pipeline.
     */
    protected mixed $passable;

    /**
     * The array of pipes.
     */
    protected array $pipes = [];

    /**
     * The method to call on each pipe.
     */
    protected string $method = 'handle';

    /**
     * Create a new pipeline instance.
     */
    public function __construct(protected Container $app)
    {
    }

    /**
     * Set the object being sent through the pipeline.
     */
    public function send(mixed $passable): self
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
     * Set the method to call on the pipes.
     */
    public function via(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     */
    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
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

    /**
     * Get a Closure that represents a slice of the application onion.
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    return $pipe($passable, $stack);
                }
                
                if (!is_object($pipe)) {
                    $pipe = $this->app->make($pipe);
                }
                
                return method_exists($pipe, 'handle')
                    ? $pipe->handle($passable, $stack)
                    : $pipe($passable, $stack);
            };
        };
    }
}