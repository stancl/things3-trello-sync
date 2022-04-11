<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait WithDefaultOrder
{
    public static function bootWithDefaultOrder()
    {
        static::addGlobalScope('orderByIndex', fn (Builder $query) => $query->orderBy('index'));
    }
}
