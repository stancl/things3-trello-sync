<?php

namespace App\Models;

use App\Models\Concerns\{HasProject, HasArea};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Parental\HasParent;

/**
 * @property-read Collection<Task> $tasks
 */
class Heading extends BaseTask
{
    use HasParent, HasProject, HasArea;

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'actionGroup');
    }
}
