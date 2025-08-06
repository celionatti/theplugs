<?php

declare(strict_types=1);

namespace Plugs\Routing;

use Plugs\Http\Request\Request;

class RouteDefinition
{
    private array $methods;
    private string $uri;
    private mixed $action;
    private ?string $name = null;
    private array $middleware = [];
    private array $parameters = [];
    private ?string $domain = null;
    private array $constraints = [];
    private ?string $prefix = null;
    private ?string $namespace = null;

    public function __construct(array $methods, string $uri, mixed $action)
    {
        $this->methods = array_map('strtoupper', $methods);
        $this->uri = $uri;
        $this->action = $action;
    }

    // Fluent interface methods
    
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function where(string|array $parameter, ?string $pattern = null): self
    {
        if (is_array($parameter)) {
            $this->constraints = array_merge($this->constraints, $parameter);
        } else {
            $this->constraints[$parameter] = $pattern;
        }
        return $this;
    }

    public function domain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    // Getters

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getUri(): string
    {
        return $this->prefix ? rtrim($this->prefix, '/') . '/' . ltrim($this->uri, '/') : $this->uri;
    }

    public function getRawUri(): string
    {
        return $this->uri;
    }

    public function getAction(): mixed
    {
        return $this->action;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    // Internal setters for group functionality

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function addGroupMiddleware(array $middleware): void
    {
        $this->middleware = array_merge($middleware, $this->middleware);
    }

    /**
     * Check if this route matches the given request
     */
    public function matches(Request $request): bool
    {
        // Check HTTP method
        if (!in_array($request->getMethod(), $this->methods)) {
            return false;
        }

        // Check domain if specified
        if ($this->domain && $request->header('host') !== $this->domain) {
            return false;
        }

        // Check URI pattern
        return $this->matchesUri($request->getUri());
    }

    /**
     * Check if URI matches this route's pattern
     */
    public function matchesUri(string $uri): bool
    {
        $pattern = $this->getUri();
        
        // Convert route pattern to regex
        $regex = $this->buildRegex($pattern);
        
        if (!preg_match($regex, $uri, $matches)) {
            return false;
        }

        // Extract parameters
        $parameters = [];
        $paramNames = $this->extractParameterNames($pattern);
        
        for ($i = 1; $i < count($matches); $i++) {
            if (isset($paramNames[$i - 1]) && $matches[$i] !== '') {
                $parameters[$paramNames[$i - 1]] = $matches[$i];
            }
        }

        $this->parameters = $parameters;
        return true;
    }

    /**
     * Build regex pattern from route URI
     */
    private function buildRegex(string $pattern): string
    {
        // Escape special regex characters except our placeholders
        $pattern = preg_quote($pattern, '#');
        
        // Replace escaped parameter placeholders with regex patterns
        // Required parameters: {id}
        $pattern = preg_replace('/\\\{([^}]+)\\\}/', '([^/]+)', $pattern);
        
        // Optional parameters: {id?}
        $pattern = preg_replace('/\\\{([^}]+)\\\?\\\}/', '([^/]*)', $pattern);

        // Apply constraints
        foreach ($this->constraints as $param => $constraint) {
            $pattern = str_replace("([^/]+)", "({$constraint})", $pattern);
        }

        return '#^' . $pattern . '$#';
    }

    /**
     * Extract parameter names from route pattern
     */
    private function extractParameterNames(string $pattern): array
    {
        preg_match_all('/\{([^}?]+)\??}/', $pattern, $matches);
        return $matches[1] ?? [];
    }
}