<?php

namespace App\Models\Concerns;

use App\Models\Area;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read ?Area $parentArea
 */
trait HasArea
{
    public function parentArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area');
    }
}
