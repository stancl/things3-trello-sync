<?php

namespace App\Models;

use App\Models\Concerns\WithTrelloLabels;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $title
 * @property mixed $shortcut
 * @property mixed $usedDate
 * @property mixed $parent
 * @property int $index
 */
class Tag extends ThingsModel
{
    use WithTrelloLabels;

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'TMTaskTag', 'tags', 'task');
    }
}
