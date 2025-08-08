<?php

declare(strict_types=1);

namespace Plugs\Database\Schema;

class ColumnDefinition
{
    private string $type;
    private string $name;
    private array $parameters;

    public function __construct(string $type, string $name, array $parameters = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function nullable(bool $value = true): self
    {
        $this->parameters['nullable'] = $value;
        return $this;
    }

    public function default($value): self
    {
        $this->parameters['default'] = $value;
        return $this;
    }

    public function unique(): self
    {
        $this->parameters['unique'] = true;
        return $this;
    }

    public function index(): self
    {
        $this->parameters['index'] = true;
        return $this;
    }

    public function primary(): self
    {
        $this->parameters['primary'] = true;
        return $this;
    }

    public function unsigned(): self
    {
        $this->parameters['unsigned'] = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->parameters['autoIncrement'] = true;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }
}