<?php

declare(strict_types=1);

namespace Plugs\Database\Schema;

class ForeignKeyDefinition
{
    private array $columns;
    private ?string $referencedTable = null;
    private array $referencedColumns = [];
    private ?string $onDelete = null;
    private ?string $onUpdate = null;

    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public function references(string|array $columns): self
    {
        $this->referencedColumns = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function on(string $table): self
    {
        $this->referencedTable = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = $action;
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        return $this->onDelete('CASCADE');
    }

    public function cascadeOnUpdate(): self
    {
        return $this->onUpdate('CASCADE');
    }

    public function nullOnDelete(): self
    {
        return $this->onDelete('SET NULL');
    }

    public function restrictOnDelete(): self
    {
        return $this->onDelete('RESTRICT');
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getReferencedTable(): ?string
    {
        return $this->referencedTable;
    }

    public function getReferencedColumns(): array
    {
        return $this->referencedColumns;
    }

    public function getOnDelete(): ?string
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): ?string
    {
        return $this->onUpdate;
    }
}