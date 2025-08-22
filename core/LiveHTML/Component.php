<?php

declare(strict_types=1);

namespace Plugs\LiveHTML;

use Plugs\View\View;
use Plugs\Exceptions\LiveHTML\LiveHTMLException;

abstract class Component
{
    protected string $id;
    protected string $name;
    protected array $state = [];
    protected array $fillable = [];
    protected array $protected = [];
    protected array $emittedEvents = [];
    protected array $dispatchedEvents = [];
    protected array $listeners = [];
    protected bool $skipRender = false;

    public function __construct(array $parameters = [])
    {
        $this->id = $this->generateId();
        $this->name = $this->getComponentName();
        
        // Initialize component
        $this->boot();
        
        // Mount with parameters
        $this->mount(...array_values($parameters));
        
        // Set initial state from properties
        $this->hydrateState();
        
        // Call booted lifecycle hook
        $this->booted();
    }

    /**
     * Generate unique component ID
     */
    protected function generateId(): string
    {
        return 'lw-' . uniqid() . '-' . mt_rand(1000, 9999);
    }

    /**
     * Get component name from class name
     */
    protected function getComponentName(): string
    {
        $className = class_basename(static::class);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
    }

    /**
     * Boot method - called before mount
     */
    protected function boot(): void
    {
        // Override in child classes
    }

    /**
     * Booted method - called after mount and hydration
     */
    protected function booted(): void
    {
        // Override in child classes
    }

    /**
     * Mount method - handle initial parameters
     */
    public function mount(): void
    {
        // Override in child classes to handle parameters
    }

    /**
     * Hydrate state from component properties
     */
    protected function hydrateState(): void
    {
        $reflection = new \ReflectionClass($this);
        
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || $property->getDeclaringClass()->getName() === Component::class) {
                continue;
            }
            
            $name = $property->getName();
            if (!in_array($name, $this->protected)) {
                $this->state[$name] = $property->getValue($this) ?? null;
            }
        }
    }

    /**
     * Dehydrate state to component properties
     */
    protected function dehydrateState(): void
    {
        foreach ($this->state as $property => $value) {
            if (property_exists($this, $property) && !in_array($property, $this->protected)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * Abstract render method - must be implemented by child classes
     */
    abstract public function render();

    /**
     * Render the component view
     */
    public function renderView(): string
    {
        $this->dehydrateState();
        
        $view = $this->render();
        
        if ($view instanceof View) {
            return $view->with('__livehtml_component', $this)->render();
        }
        
        if (is_string($view)) {
            // Assume it's a view name
            return View::make($view)
                ->with($this->state)
                ->with('__livehtml_component', $this)
                ->render();
        }
        
        throw new LiveHTMLException('Component render method must return a View instance or view name');
    }

    /**
     * Get component ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set component ID
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get component name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get component state
     */
    public function getState(): array
    {
        $this->hydrateState();
        return $this->state;
    }

    /**
     * Set component state
     */
    public function setState(array $state): void
    {
        $this->state = array_merge($this->state, $state);
        $this->dehydrateState();
    }

    /**
     * Set a single property
     */
    public function setProperty(string $name, $value): void
    {
        if ($this->isPropertyFillable($name)) {
            $this->state[$name] = $value;
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * Check if property is fillable
     */
    public function isPropertyFillable(string $property): bool
    {
        // If fillable is empty, all properties are fillable except protected
        if (empty($this->fillable)) {
            return !in_array($property, $this->protected);
        }
        
        return in_array($property, $this->fillable);
    }

    /**
     * Generate checksum for component state
     */
    public function getChecksum(): string
    {
        return hash('sha256', json_encode($this->getState()));
    }

    /**
     * Emit an event
     */
    protected function emit(string $event, ...$parameters): void
    {
        $this->emittedEvents[] = [
            'event' => $event,
            'params' => $parameters,
        ];
    }

    /**
     * Emit an event to parent component
     */
    protected function emitUp(string $event, ...$parameters): void
    {
        $this->emittedEvents[] = [
            'event' => $event,
            'params' => $parameters,
            'to' => 'parent',
        ];
    }

    /**
     * Emit an event to specific component
     */
    protected function emitTo(string $component, string $event, ...$parameters): void
    {
        $this->emittedEvents[] = [
            'event' => $event,
            'params' => $parameters,
            'to' => $component,
        ];
    }

    /**
     * Dispatch browser event
     */
    protected function dispatchBrowserEvent(string $event, array $data = []): void
    {
        $this->dispatchedEvents[] = [
            'event' => $event,
            'data' => $data,
        ];
    }

    /**
     * Get emitted events
     */
    public function getEmittedEvents(): array
    {
        return $this->emittedEvents;
    }

    /**
     * Get dispatched events
     */
    public function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    /**
     * Clear events
     */
    public function clearEvents(): void
    {
        $this->emittedEvents = [];
        $this->dispatchedEvents = [];
    }

    /**
     * Redirect response
     */
    protected function redirect(string $url): void
    {
        $this->dispatchBrowserEvent('redirect', ['url' => $url]);
    }

    /**
     * Redirect with message
     */
    protected function redirectWithMessage(string $url, string $message, string $type = 'success'): void
    {
        // Store message in session
        $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
        $this->redirect($url);
    }

    /**
     * Skip render on next request
     */
    protected function skipRender(): void
    {
        $this->skipRender = true;
    }

    /**
     * Check if render should be skipped
     */
    public function shouldSkipRender(): bool
    {
        return $this->skipRender;
    }

    /**
     * Reset component to initial state
     */
    public function reset(...$properties): void
    {
        if (empty($properties)) {
            $this->state = [];
            $this->hydrateState();
        } else {
            foreach ($properties as $property) {
                if (isset($this->state[$property])) {
                    unset($this->state[$property]);
                }
                if (property_exists($this, $property)) {
                    $reflection = new \ReflectionProperty($this, $property);
                    $this->{$property} = $reflection->hasDefaultValue() 
                        ? $reflection->getDefaultValue() 
                        : null;
                }
            }
        }
    }

    /**
     * Validate component data
     */
    protected function validate(array $rules, array $messages = []): array
    {
        // Basic validation - you might want to integrate with a proper validator
        $errors = [];
        
        foreach ($rules as $property => $rule) {
            $value = $this->state[$property] ?? null;
            
            if ($rule === 'required' && empty($value)) {
                $errors[$property] = $messages[$property] ?? "The {$property} field is required.";
            }
        }
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($errors));
        }
        
        return $this->state;
    }

    /**
     * Magic getter for component properties
     */
    public function __get(string $name)
    {
        return $this->state[$name] ?? null;
    }

    /**
     * Magic setter for component properties
     */
    public function __set(string $name, $value): void
    {
        if ($this->isPropertyFillable($name)) {
            $this->state[$name] = $value;
        }
    }

    /**
     * Magic isset for component properties
     */
    public function __isset(string $name): bool
    {
        return isset($this->state[$name]);
    }

    /**
     * Generate JavaScript reference for this component
     */
    public function jsReference(?string $method = null): string
    {
        if ($method) {
            return "@this.call('{$method}')";
        }
        return "@this";
    }

    /**
     * Generate entangled JavaScript for two-way binding
     */
    public function entangle(string $property): string
    {
        return "@entangle('{$property}')";
    }

    /**
     * Add real-time validation
     */
    protected function updated(string $property, $value): void
    {
        // Override in child classes for real-time validation
    }

    /**
     * Handle property updates
     */
    public function updatedProperty(string $property, $value): void
    {
        $this->setProperty($property, $value);
        
        // Call specific updated method if exists
        $method = 'updated' . str_replace('_', '', ucwords($property, '_'));
        if (method_exists($this, $method)) {
            $this->{$method}($value);
        }
        
        // Call general updated method
        $this->updated($property, $value);
    }

    /**
     * Lifecycle hooks
     */
    public function updating(string $property, $value): void
    {
        // Override in child classes
    }

    public function updatingProperty(string $property, $value): void
    {
        $this->updating($property, $value);
        
        // Call specific updating method if exists
        $method = 'updating' . str_replace('_', '', ucwords($property, '_'));
        if (method_exists($this, $method)) {
            $this->{$method}($value);
        }
    }

    /**
     * Handle dynamic method calls
     */
    public function __call(string $method, array $parameters)
    {
        // Handle listener methods
        if (str_starts_with($method, 'on') && isset($this->listeners[strtolower(substr($method, 2))])) {
            return $this->{$this->listeners[strtolower(substr($method, 2))]}(...$parameters);
        }
        
        throw new \BadMethodCallException("Method [{$method}] does not exist on component [" . static::class . "].");
    }
}