<?php

declare(strict_types=1);

namespace Plugs\Database\Traits;

trait HasRelationships
{
    protected array $relations = [];

    public function setRelation(string $relation, $value): static
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    public function getRelation(string $relation)
    {
        return $this->relations[$relation] ?? null;
    }

    public function relationLoaded(string $relation): bool
    {
        return array_key_exists($relation, $this->relations);
    }
}