<?php

declare(strict_types=1);

namespace Plugs\Database\Eloquent;

use Plugs\Database\QueryBuilder;
use Plugs\Database\Eloquent\Model;
use Plugs\Database\Eloquent\EloquentBuilder;

abstract class Relation
{
    protected string $related;
    protected Model $parent;
    protected EloquentBuilder $query; // Changed from QueryBuilder

    public function __construct(string $related, Model $parent)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->query = $this->newQuery();
    }

    abstract public function addConstraints(): void;
    abstract public function addEagerConstraints(array $models): self;
    abstract public function match(array $models, string $relation): array;
    abstract public function getResults();

    protected function newQuery(): EloquentBuilder // Changed return type
    {
        $instance = $this->newRelatedInstance();
        return $instance::query(); // Use the model's query builder
    }

    protected function newRelatedInstance(): Model
    {
        return new $this->related;
    }

    public function get(): array
    {
        return $this->getResults();
    }

    public function first(): ?Model
    {
        $result = $this->query->first();
        return $result; // EloquentBuilder already returns Model or null
    }

    // Add common query builder methods
    public function where(string $column, $operator = null, $value = null): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->query->offset($offset);
        return $this;
    }

    public function count(): int
    {
        return $this->query->count();
    }

    // Removed hydrate method - EloquentBuilder handles this

    public function __call(string $method, array $parameters)
    {
        $result = $this->query->$method(...$parameters);

        // If it returns the query builder, return $this for chaining
        if ($result instanceof EloquentBuilder || $result instanceof QueryBuilder) {
            return $this;
        }

        return $result;
    }
}
