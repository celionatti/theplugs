<?php

declare(strict_types=1);

namespace Plugs\Exceptions\View;

use Exception;

class ViewException extends Exception
{
    protected string $view;

    public function __construct(string $message, string $view = '', int $code = 0, ?Exception $previous = null)
    {
        $this->view = $view;
        parent::__construct($message, $code, $previous);
    }

    public function getView(): string
    {
        return $this->view;
    }
}