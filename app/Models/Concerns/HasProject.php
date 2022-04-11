<?php

namespace App\Models\Concerns;

use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read ?Project $parentProject
 */
trait HasProject
{
    public function parentProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project');
    }
}
