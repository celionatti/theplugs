<?php

declare(strict_types=1);

namespace Plugs\Database\Traits;

use Plugs\Database\Eloquent\EloquentBuilder;

trait ModelHelpers
{
    public function newInstance(array $attributes = []): static
    {
        $model = new static($attributes);
        $model->exists = false;
        return $model;
    }

    public function setRawAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
        $this->original = $attributes;
    }

    public function newQuery(): EloquentBuilder
    {
        return static::query();
    }
}