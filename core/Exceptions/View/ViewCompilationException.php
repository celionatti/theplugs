<?php

declare(strict_types=1);

namespace Plugs\Exceptions\View;

use Exception;
use Plugs\Exceptions\View\ViewException;

class ViewCompilationException extends ViewException
{
    protected string $file;
    protected int $line;

    public function __construct(string $message, string $view, string $file = '', int $line = 0, ?Exception $previous = null)
    {
        $this->file = $file;
        $this->line = $line;
        
        $fullMessage = "View compilation failed for [{$view}]: {$message}";
        if ($file) {
            $fullMessage .= " in {$file}";
            if ($line > 0) {
                $fullMessage .= " on line {$line}";
            }
        }
        
        parent::__construct($fullMessage, $view, 0, $previous);
    }

    public function file(): string
    {
        return $this->file;
    }

    public function line(): int
    {
        return $this->line;
    }
}