<?php

declare(strict_types=1);

namespace Plugs\Database\Eloquent;

use Plugs\Database\QueryBuilder;
use Plugs\Database\DatabaseConfig;
use Plugs\Database\Eloquent\Model;

abstract class Relation
{
    protected string $related;
    protected Model $parent;
    protected QueryBuilder $query;

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

    protected function newQuery(): QueryBuilder
    {
        $instance = new $this->related;
        $builder = new QueryBuilder(DatabaseConfig::getConnection());
        return $builder->table($instance->getTable());
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
        return $result ? $this->hydrate([$result])[0] : null;
    }

    protected function hydrate(array $items): array
    {
        $instance = $this->newRelatedInstance();
        $models = [];

        foreach ($items as $item) {
            $model = $instance->newInstance();
            $model->setRawAttributes($item);
            $model->exists = true;
            $models[] = $model;
        }

        return $models;
    }

    public function __call(string $method, array $parameters)
    {
        $result = $this->query->$method(...$parameters);
        
        if ($result instanceof QueryBuilder) {
            return $this;
        }
        
        return $result;
    }
}