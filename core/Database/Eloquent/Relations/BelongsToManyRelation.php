<?php

declare(strict_types=1);

namespace Plugs\Database\Eloquent\Relations;

use Plugs\Database\QueryBuilder;
use Plugs\Database\DatabaseConfig;
use Plugs\Database\Eloquent\Model;
use Plugs\Database\Eloquent\Pivot;
use Plugs\Database\Eloquent\Relation;

class BelongsToManyRelation extends Relation
{
    protected string $table;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;
    protected array $pivotColumns = [];

    public function __construct(string $related, Model $parent, string $table, 
                               string $foreignPivotKey, string $relatedPivotKey)
    {
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        parent::__construct($related, $parent);
        $this->addConstraints();
    }

    public function addConstraints(): void
    {
        if ($this->parent->exists) {
            $relatedTable = $this->newRelatedInstance()->getTable();
            $relatedKey = $this->newRelatedInstance()->getKeyName();
            $joinCondition = "{$relatedTable}.{$relatedKey} = {$this->table}.{$this->relatedPivotKey}";
            $this->query->join($this->table, $joinCondition)
                        ->where("{$this->table}.{$this->foreignPivotKey}", '=', $this->parent->getAttribute($this->parent->getKeyName()));
        }
    }

    public function addEagerConstraints(array $models): self
    {
        $keys = [];
        foreach ($models as $model) {
            if (($key = $model->getAttribute($model->getKeyName())) !== null) {
                $keys[] = $key;
            }
        }

        if (!empty($keys)) {
            $relatedTable = $this->newRelatedInstance()->getTable();
            $relatedKey = $this->newRelatedInstance()->getKeyName();
            $joinCondition = "{$relatedTable}.{$relatedKey} = {$this->table}.{$this->relatedPivotKey}";
            $this->query->join($this->table, $joinCondition)
                        ->whereIn("{$this->table}.{$this->foreignPivotKey}", array_unique($keys));
        }

        return $this;
    }

    public function match(array $models, string $relation): array
    {
        $results = $this->get();
        $dictionary = [];

        foreach ($results as $result) {
            $key = $result->pivot->{$this->foreignPivotKey};
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }
            $dictionary[$key][] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($model->getKeyName());
            $model->setRelation($relation, $dictionary[$key] ?? []);
        }

        return $models;
    }

    public function getResults(): array
    {
        $results = $this->query->get();
        return $this->hydrate($results);
    }

    public function withPivot(string|array $columns): self
    {
        $this->pivotColumns = array_merge($this->pivotColumns, 
                                         is_array($columns) ? $columns : func_get_args());
        return $this;
    }

    public function attach($id, array $attributes = []): void
    {
        $attributes[$this->foreignPivotKey] = $this->parent->getAttribute($this->parent->getKeyName());
        $attributes[$this->relatedPivotKey] = $id;

        $builder = new QueryBuilder(DatabaseConfig::getConnection());
        $builder->table($this->table)->insert($attributes);
    }

    public function detach($ids = null): int
    {
        $builder = new QueryBuilder(DatabaseConfig::getConnection());
        $query = $builder->table($this->table)
                        ->where($this->foreignPivotKey, $this->parent->getAttribute($this->parent->getKeyName()));

        if ($ids !== null) {
            $ids = is_array($ids) ? $ids : [$ids];
            $query->whereIn($this->relatedPivotKey, $ids);
        }

        return $query->delete();
    }

    public function sync(array $ids): array
    {
        $current = $this->getCurrentIds();
        $detach = array_diff($current, array_keys($ids));
        $attach = array_diff(array_keys($ids), $current);

        $changes = [
            'attached' => [],
            'detached' => [],
            'updated' => []
        ];

        if (!empty($detach)) {
            $this->detach($detach);
            $changes['detached'] = $detach;
        }

        foreach ($attach as $id) {
            $this->attach($id, $ids[$id] ?? []);
            $changes['attached'][] = $id;
        }

        return $changes;
    }

    protected function getCurrentIds(): array
    {
        $builder = new QueryBuilder(DatabaseConfig::getConnection());
        $results = $builder->table($this->table)
                          ->where($this->foreignPivotKey, $this->parent->getAttribute($this->parent->getKeyName()))
                          ->get();

        return array_column($results, $this->relatedPivotKey);
    }

    protected function hydrate(array $items): array
    {
        $models = parent::hydrate($items);

        foreach ($models as $model) {
            $pivot = new Pivot();
            $pivot->setRawAttributes([
                $this->foreignPivotKey => $this->parent->getAttribute($this->parent->getKeyName()),
                $this->relatedPivotKey => $model->getAttribute($model->getKeyName())
            ]);
            $model->setRelation('pivot', $pivot);
        }

        return $models;
    }
}