<?php

declare(strict_types=1);

namespace Plugs\View\Engines;

use Plugs\Exceptions\View\ViewException;
use Plugs\View\Contracts\EngineInterface;


class PhpEngine implements EngineInterface
{
    public function get(string $path, array $data = []): string
    {
        try {
            return $this->evaluatePath($path, $data);
        } catch (\Exception $e) {
            throw new ViewException("Error rendering view: " . $e->getMessage(), basename($path), 0, $e);
        }
    }

    protected function evaluatePath(string $path, array $data): string
    {
        $obLevel = ob_get_level();

        ob_start();

        extract($data, EXTR_SKIP);

        try {
            include $path;
        } catch (\Exception $e) {
            $this->handleViewException($e, $obLevel);
        } catch (\Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    protected function handleViewException(\Throwable $e, int $obLevel): void
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}