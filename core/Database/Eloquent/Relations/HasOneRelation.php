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
            $this->query->where($this->foreignKey, $this->parent->getAttribute($this->localKey));
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
            }
        }

        return $models;
    }

    public function getResults(): ?Model
    {
        $result = $this->query->first();
        return $result ? $this->hydrate([$result])[0] : null;
    }

    public function save(Model $model): bool
    {
        $model->setAttribute($this->foreignKey, $this->parent->getAttribute($this->localKey));
        return $model->save();
    }

    public function create(array $attributes = []): Model
    {
        $attributes[$this->foreignKey] = $this->parent->getAttribute($this->localKey);
        return $this->related::create($attributes);
    }
}