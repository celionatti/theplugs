<?php

declare(strict_types=1);

namespace Plugs\Database\Eloquent;

use Plugs\Database\QueryBuilder;
use Plugs\Database\Eloquent\Model;
use Plugs\Exceptions\Database\ModelNotFoundException;

class EloquentBuilder
{
    protected QueryBuilder $query;
    protected Model $model;
    protected array $eagerLoad = [];
    protected bool $removedGlobalScopes = false;

    public function __construct(QueryBuilder $query, Model $model)
    {
        $this->query = $query;
        $this->model = $model;
    }

    // Proxy methods to QueryBuilder
    public function select(array|string $columns = ['*']): self
    {
        $this->query->select($columns);
        return $this;
    }

    public function where(string $column, $operator = null, $value = null): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    public function orWhere(string $column, $operator = null, $value = null): self
    {
        $this->query->orWhere($column, $operator, $value);
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        $this->query->whereNotIn($column, $values);
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->query->whereNull($column);
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->query->whereNotNull($column);
        return $this;
    }

    public function join(string $table, string $first, ?string $operator = null, ?string $second = null): self
    {
        $this->query->join($table, $first, $operator, $second);
        return $this;
    }

    public function leftJoin(string $table, string $first, ?string $operator = null, ?string $second = null): self
    {
        $this->query->leftJoin($table, $first, $operator, $second);
        return $this;
    }

    public function rightJoin(string $table, string $first, ?string $operator = null, ?string $second = null): self
    {
        $this->query->rightJoin($table, $first, $operator, $second);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    public function groupBy(string|array $groups): self
    {
        $this->query->groupBy($groups);
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

    // Eloquent-specific methods
    public function with(string|array $relations): self
    {
        $relations = is_string($relations) ? [$relations] : $relations;

        foreach ($relations as $relation) {
            $this->eagerLoad[$relation] = [];
        }

        return $this;
    }

    public function withoutGlobalScopes(): self
    {
        // Create a fresh query without global scopes
        $freshQuery = new QueryBuilder($this->query->pdo);
        $freshQuery->table($this->model->getTable());
        
        // Copy over all existing conditions except soft delete constraints
        $this->copyQueryState($this->query, $freshQuery);
        
        $this->query = $freshQuery;
        $this->removedGlobalScopes = true;

        return $this;
    }

    public function onlyTrashed(): self
    {
        if ($this->model->usesSoftDeletes()) {
            // First remove any existing soft delete constraints
            $this->withoutGlobalScopes();
            // Then add constraint to only show trashed records
            $this->query->whereNotNull($this->model->getDeletedAtColumn());
        }

        return $this;
    }

    public function withTrashed(): self
    {
        if ($this->model->usesSoftDeletes()) {
            // Remove soft delete constraints to include both trashed and non-trashed
            $this->withoutGlobalScopes();
        }

        return $this;
    }

    protected function copyQueryState(QueryBuilder $from, QueryBuilder $to): void
    {
        // This is a simplified copy - you might need to extend this
        // based on your QueryBuilder implementation
        $reflection = new \ReflectionClass($from);
        
        $properties = ['selects', 'joins', 'groupBy', 'orderBy', 'limitValue', 'offsetValue'];
        
        foreach ($properties as $property) {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);
                $value = $prop->getValue($from);
                
                $toProp = (new \ReflectionClass($to))->getProperty($property);
                $toProp->setAccessible(true);
                $toProp->setValue($to, $value);
            }
        }
        
        // Copy wheres but filter out soft delete constraints
        $wheresProperty = $reflection->getProperty('wheres');
        $wheresProperty->setAccessible(true);
        $wheres = $wheresProperty->getValue($from);
        
        $bindingsProperty = $reflection->getProperty('bindings');
        $bindingsProperty->setAccessible(true);
        $bindings = $bindingsProperty->getValue($from);
        
        $filteredWheres = [];
        $filteredBindings = [];
        
        foreach ($wheres as $index => $where) {
            // Skip soft delete constraints
            if ($this->model->usesSoftDeletes() && 
                isset($where['column']) && 
                str_contains($where['column'], $this->model->getDeletedAtColumn()) &&
                $where['type'] === 'null') {
                continue;
            }
            
            $filteredWheres[] = $where;
            
            // Copy corresponding bindings
            if (isset($bindings[$index])) {
                $filteredBindings[] = $bindings[$index];
            }
        }
        
        $toWheresProperty = (new \ReflectionClass($to))->getProperty('wheres');
        $toWheresProperty->setAccessible(true);
        $toWheresProperty->setValue($to, $filteredWheres);
        
        $toBindingsProperty = (new \ReflectionClass($to))->getProperty('bindings');
        $toBindingsProperty->setAccessible(true);
        $toBindingsProperty->setValue($to, $filteredBindings);
    }

    // Result methods that return models
    public function get(): array
    {
        $results = $this->query->get();
        $models = $this->hydrate($results);

        if (!empty($this->eagerLoad)) {
            $models = $this->loadRelations($models);
        }

        return $models;
    }

    public function first(): ?Model
    {
        $result = $this->query->first();

        if ($result === null) {
            return null;
        }

        $model = $this->newModelInstance($result);

        if (!empty($this->eagerLoad)) {
            $this->loadRelations([$model]);
        }

        return $model;
    }

    public function firstOrFail(): Model
    {
        $model = $this->first();

        if ($model === null) {
            throw new ModelNotFoundException('No query results for model [' . get_class($this->model) . ']');
        }

        return $model;
    }

    public function find($id): ?Model
    {
        return $this->where($this->model->getKeyName(), '=', $id)->first();
    }

    public function findOrFail($id): Model
    {
        $model = $this->find($id);

        if ($model === null) {
            throw new ModelNotFoundException("No query results for model [" . get_class($this->model) . "] {$id}");
        }

        return $model;
    }

    public function count(): int
    {
        return $this->query->count();
    }

    public function exists(): bool
    {
        return $this->query->exists();
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = $this->count();
        $offset = ($page - 1) * $perPage;

        $results = $this->offset($offset)->limit($perPage)->get();

        return [
            'data' => $results,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }

    // Aggregate methods
    public function max(string $column)
    {
        return $this->query->select(["MAX({$column}) as aggregate"])->first()['aggregate'] ?? null;
    }

    public function min(string $column)
    {
        return $this->query->select(["MIN({$column}) as aggregate"])->first()['aggregate'] ?? null;
    }

    public function avg(string $column)
    {
        return $this->query->select(["AVG({$column}) as aggregate"])->first()['aggregate'] ?? null;
    }

    public function sum(string $column)
    {
        return $this->query->select(["SUM({$column}) as aggregate"])->first()['aggregate'] ?? null;
    }

    // Modification methods
    public function update(array $values): int
    {
        // For bulk updates, we can use the query builder directly
        // But we should also fire model events if needed
        return $this->query->update($values);
    }

    public function delete(): int
    {
        // Get all models first to fire individual delete events
        $models = $this->get();
        $count = 0;

        foreach ($models as $model) {
            if ($model->delete()) {
                $count++;
            }
        }

        return $count;
    }

    public function forceDelete(): int
    {
        // Direct database deletion without model events
        return $this->query->delete();
    }

    // Helper methods
    protected function hydrate(array $items): array
    {
        $models = [];

        foreach ($items as $item) {
            $models[] = $this->newModelInstance($item);
        }

        return $models;
    }

    protected function newModelInstance(array $attributes = []): Model
    {
        $model = $this->model->newInstance();
        $model->setRawAttributes($attributes, true); // Set sync = true to mark as original
        $model->setExisting(true);

        return $model;
    }

    protected function loadRelations(array $models): array
    {
        foreach ($this->eagerLoad as $relation => $constraints) {
            $models = $this->loadRelation($models, $relation, $constraints);
        }

        return $models;
    }

    protected function loadRelation(array $models, string $relation, array $constraints): array
    {
        if (empty($models)) {
            return $models;
        }

        $relationInstance = $models[0]->$relation();

        return $relationInstance->addEagerConstraints($models)->match($models, $relation);
    }

    public function toSql(): string
    {
        return $this->query->toSql();
    }

    public function getBindings(): array
    {
        return $this->query->getBindings();
    }

    // Magic method to handle dynamic where clauses
    public function __call(string $method, array $parameters)
    {
        if (str_starts_with($method, 'where')) {
            return $this->dynamicWhere($method, $parameters);
        }

        // Forward to the query builder
        if (method_exists($this->query, $method)) {
            $result = $this->query->$method(...$parameters);
            return $result instanceof QueryBuilder ? $this : $result;
        }

        throw new \BadMethodCallException("Call to undefined method {$method}");
    }

    protected function dynamicWhere(string $method, array $parameters): self
    {
        $finder = substr($method, 5);
        $segments = preg_split('/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE);

        $connector = 'and';
        $index = 0;

        foreach ($segments as $segment) {
            if ($segment !== 'And' && $segment !== 'Or') {
                $column = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($segment)));
                $value = $parameters[$index] ?? null;

                if ($connector === 'and') {
                    $this->where($column, $value);
                } else {
                    $this->orWhere($column, $value);
                }

                $index++;
            } else {
                $connector = strtolower($segment);
            }
        }

        return $this;
    }
}