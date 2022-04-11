<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string uuid
 * @property Carbon userModificationDate
 * @property Carbon creationDate
 */
abstract class ThingsModel extends Model
{
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    protected $dateFormat = 'U';
    const CREATED_AT = 'creationDate';
    const UPDATED_AT = 'userModificationDate';

    protected $guarded = [];

    public function getCasts()
    {
        return [
            ...parent::getCasts(),
            ...collect($this->timestamps())->mapWithKeys(fn ($timestamp) => [$timestamp => 'datetime'])->toArray(),
            'creationDate' => 'datetime',
            'userModificationDate' => 'datetime',
        ];
    }

    protected function timestamps(): array
    {
        // implemented in children

        return [];
    }

    public function getTable()
    {
        return 'TM' . Str::studly(class_basename($this));
    }
}
