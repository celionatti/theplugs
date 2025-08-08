<?php

declare(strict_types=1);

namespace Plugs\Database\Eloquent;

use Plugs\Database\Eloquent\Model;

class Pivot extends Model
{
    public $incrementing = false;
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;
    }
}