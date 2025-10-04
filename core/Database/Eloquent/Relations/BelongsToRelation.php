<?php

declare(strict_types=1);

namespace Plugs\Database\Eloquent\Relations;

use Plugs\Database\Eloquent\Model;
use Plugs\Database\Eloquent\Relation;

class BelongsToRelation extends Relation
{
    protected string $foreignKey;
    protected string $ownerKey;

    public function __construct(string $related, Model $parent, string $foreignKey, string $ownerKey)
    {
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
        parent::__construct($related, $parent);
        $this->addConstraints();
    }

    public function addConstraints(): void
    {
        if ($this->parent->exists) {
            $foreignKeyValue = $this->parent->getAttribute($this->foreignKey);
            if ($foreignKeyValue !== null) {
                $this->query->where($this->ownerKey, '=', $foreignKeyValue);
            }
        }
    }

    public function addEagerConstraints(array $models): self
    {
        $keys = [];
        foreach ($models as $model) {
            if (($key = $model->getAttribute($this->foreignKey)) !== null) {
                $keys[] = $key;
            }
        }

        if (!empty($keys)) {
            $this->query->whereIn($this->ownerKey, array_unique($keys));
        }

        return $this;
    }

    public function match(array $models, string $relation): array
    {
        $results = $this->get();
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->getAttribute($this->ownerKey)] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->foreignKey);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                // Set null if no relation found
                $model->setRelation($relation, null);
            }
        }

        return $models;
    }

    public function getResults(): ?Model
    {
        return $this->query->first();
    }

    public function associate(?Model $model): Model
    {
        if ($model === null) {
            return $this->dissociate();
        }

        $this->parent->setAttribute($this->foreignKey, $model->getAttribute($this->ownerKey));
        $this->parent->setRelation(
            $this->getRelationName(),
            $model
        );

        return $this->parent;
    }

    public function dissociate(): Model
    {
        $this->parent->setAttribute($this->foreignKey, null);
        $this->parent->setRelation($this->getRelationName(), null);

        return $this->parent;
    }

    // Helper to get relation name from backtrace
    protected function getRelationName(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        return $backtrace[2]['function'] ?? 'relation';
    }
}
