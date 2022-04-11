<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $title
 * @property bool $visible
 * @property int $index
 * @property mixed $cachedTags
 *
 * @property-read Collection<Task> $tasks
 */
class Area extends ThingsModel
{
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'area');
    }

    /** All of the task's tags. */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'TMAreaTag', 'areas', 'tags');
    }
}
