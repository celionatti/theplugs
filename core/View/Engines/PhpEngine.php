<?php

declare(strict_types=1);

namespace Plugs\View\Engines;

use Exception;
use Plugs\Exceptions\View\ViewException;

class PhpEngine implements EngineInterface
{
    /**
     * Get the evaluated contents of the view.
     */
    public function get(string $path, array $data = []): string
    {
        return $this->evaluatePath($path, $data);
    }

    /**
     * Get the evaluated contents of the view at the given path.
     */
    protected function evaluatePath(string $path, array $data): string
    {
        $obLevel = ob_get_level();

        ob_start();

        // Extract data but avoid variable collisions
        $__path = $path;
        $__data = $data;
        extract($data, EXTR_SKIP);

        try {
            include $__path;
        } catch (Exception $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    /**
     * Handle a view exception.
     */
    protected function handleViewException(Exception $e, int $obLevel): void
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw new ViewException("Error rendering PHP template: " . $e->getMessage(), 0, $e);
    }
}