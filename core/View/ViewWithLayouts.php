<?php

declare(strict_types=1);

namespace Plugs\View;

class ViewWithLayouts extends View
{
    protected array $sections = [];
    protected array $sectionStack = [];
    protected ?string $extendedView = null;

    public function render(): string
    {
        $contents = $this->renderContents();

        if ($this->extendedView) {
            $parent = $this->factory->make($this->extendedView, $this->getData());
            
            if ($parent instanceof ViewWithLayouts) {
                $parent->sections = array_merge($parent->sections, $this->sections);
                return $parent->render();
            }
        }

        return $contents;
    }

    public function extend(string $view): void
    {
        $this->extendedView = $view;
    }

    public function startSection(string $section): void
    {
        $this->sectionStack[] = $section;
        ob_start();
    }

    public function stopSection(): void
    {
        if (empty($this->sectionStack)) {
            throw new \InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $last = array_pop($this->sectionStack);
        $this->sections[$last] = ob_get_clean();
    }

    public function yieldSection(string $section): string
    {
        return $this->sections[$section] ?? '';
    }

    public function hasSection(string $section): bool
    {
        return isset($this->sections[$section]);
    }

    public function getSection(string $section, string $default = ''): string
    {
        return $this->sections[$section] ?? $default;
    }

    public function getSections(): array
    {
        return $this->sections;
    }
}