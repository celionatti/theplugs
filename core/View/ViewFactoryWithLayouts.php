<?php

declare(strict_types=1);

namespace Plugs\View;

use Plugs\View\ViewWithLayouts;
use Plugs\View\Contracts\ViewInterface;

class ViewFactoryWithLayouts extends ViewFactory
{
    public function make(string $view, array $data = []): ViewInterface
    {
        $path = $this->finder->find($view);
        $engine = $this->getEngineFromPath($path);

        return new ViewWithLayouts($this, $engine, $view, $path, $data);
    }
}