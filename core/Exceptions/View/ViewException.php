<?php

declare(strict_types=1);

namespace Plugs\Exceptions\View;

use Exception;

class ViewException extends Exception
{
    protected string $viewName = '';
    protected array $viewData = [];

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Set the view name that caused the exception.
     */
    public function setView(string $viewName): self
    {
        $this->viewName = $viewName;
        return $this;
    }

    /**
     * Get the view name that caused the exception.
     */
    public function getView(): string
    {
        return $this->viewName;
    }

    /**
     * Set the view data that was being used.
     */
    public function setViewData(array $data): self
    {
        $this->viewData = $data;
        return $this;
    }

    /**
     * Get the view data that was being used.
     */
    public function getViewData(): array
    {
        return $this->viewData;
    }

    /**
     * Create a new exception for a missing template.
     */
    public static function templateNotFound(string $template, array $paths = []): self
    {
        $message = "View '{$template}' not found";
        
        if (!empty($paths)) {
            $message .= " in paths: " . implode(', ', $paths);
        }

        return (new static($message))->setView($template);
    }

    /**
     * Create a new exception for compilation errors.
     */
    public static function compilationFailed(string $template, string $error): self
    {
        $message = "Failed to compile view '{$template}': {$error}";
        return (new static($message))->setView($template);
    }

    /**
     * Create a new exception for rendering errors.
     */
    public static function renderingFailed(string $template, string $error, ?\Throwable $previous = null): self
    {
        $message = "Failed to render view '{$template}': {$error}";
        return (new static($message, 0, $previous))->setView($template);
    }

    /**
     * Create a new exception for section errors.
     */
    public static function sectionError(string $template, string $section, string $error): self
    {
        $message = "Section error in view '{$template}', section '{$section}': {$error}";
        return (new static($message))->setView($template);
    }

    /**
     * Create a new exception for engine errors.
     */
    public static function engineError(string $engine, string $error): self
    {
        $message = "Engine '{$engine}' error: {$error}";
        return new static($message);
    }
}