<?php

namespace App\Models;

use App\Models\Concerns\HasArea;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Parental\HasParent;

/**
 * @property-read Collection<Task> $tasks
 * @property-read Collection<Heading> $headings
 */
class Project extends BaseTask
{
    use HasParent, HasArea;

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project');
    }

    public function headings(): HasMany
    {
        return $this->hasMany(Heading::class, 'project');
    }
}
