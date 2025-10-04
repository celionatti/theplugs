<?php

declare(strict_types=1);

namespace Plugs\Database\Eloquent\Relations;

use Plugs\Database\Eloquent\Model;
use Plugs\Database\Eloquent\Relation;

class HasOneRelation extends Relation
{
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(string $related, Model $parent, string $foreignKey, string $localKey)
    {
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        parent::__construct($related, $parent);
        $this->addConstraints();
    }

    public function addConstraints(): void
    {
        if ($this->parent->exists) {
            $localKeyValue = $this->parent->getAttribute($this->localKey);
            if ($localKeyValue !== null) {
                $this->query->where($this->foreignKey, '=', $localKeyValue);
            }
        }
    }

    public function addEagerConstraints(array $models): self
    {
        $keys = [];
        foreach ($models as $model) {
            if (($key = $model->getAttribute($this->localKey)) !== null) {
                $keys[] = $key;
            }
        }

        if (!empty($keys)) {
            $this->query->whereIn($this->foreignKey, array_unique($keys));
        }

        return $this;
    }

    public function match(array $models, string $relation): array
    {
        $results = $this->get();
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->getAttribute($this->foreignKey)] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                $model->setRelation($relation, null);
            }
        }

        return $models;
    }

    public function getResults(): ?Model
    {
        return $this->query->first();
    }

    public function save(Model $model): bool
    {
        $model->setAttribute($this->foreignKey, $this->parent->getAttribute($this->localKey));
        return $model->save();
    }

    public function create(array $attributes = []): Model
    {
        $attributes[$this->foreignKey] = $this->parent->getAttribute($this->localKey);
        
        $instance = $this->newRelatedInstance();
        $instance->fill($attributes);
        $instance->save();
        
        return $instance;
    }

    public function update(array $attributes): bool
    {
        return $this->query->update($attributes) > 0;
    }

    public function delete(): bool
    {
        $model = $this->getResults();
        return $model ? $model->delete() : false;
    }
}