<?php

declare(strict_types=1);

namespace Plugs\View;

use Plugs\View\Contracts\ViewInterface;
use Plugs\View\Contracts\EngineInterface;

class View implements ViewInterface
{
    protected string $view;
    protected string $path;
    protected array $data;
    protected EngineInterface $engine;
    protected ViewFactory $factory;

    public function __construct(ViewFactory $factory, EngineInterface $engine, string $view, string $path, array $data = [])
    {
        $this->view = $view;
        $this->path = $path;
        $this->data = $data;
        $this->engine = $engine;
        $this->factory = $factory;
    }

    public function getName(): string
    {
        return $this->view;
    }

    public function getData(): array
    {
        return array_merge($this->factory->getShared(), $this->data);
    }

    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function render(): string
    {
        try {
            $contents = $this->renderContents();
            return $contents;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function renderContents(): string
    {
        return $this->engine->get($this->path, $this->gatherData());
    }

    protected function gatherData(): array
    {
        $data = array_merge($this->factory->getShared(), $this->data);
        
        return array_map(function ($value) {
            return $value instanceof ViewInterface ? $value->render() : $value;
        }, $data);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getEngine(): EngineInterface
    {
        return $this->engine;
    }

    public function getFactory(): ViewFactory
    {
        return $this->factory;
    }
}
