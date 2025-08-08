<?php

declare(strict_types=1);

namespace Plugs\Database\Eloquent;

use JsonSerializable;

class Collection implements \IteratorAggregate, \Countable, JsonSerializable
{
    protected array $items = [];

    public function __construct($items = [])
    {
        $this->items = is_array($items) ? $items : [$items];
    }

    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function jsonSerialize(): array
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }
            return $value;
        }, $this->items);
    }

    public function toArray(): array
    {
        return array_map(function ($value) {
            return $value instanceof Model ? $value->toArray() : $value;
        }, $this->items);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}