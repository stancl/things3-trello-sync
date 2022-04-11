<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use App\Models\Concerns\WithDefaultOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $title
 * @property int $status
 * @property ?Carbon $stopDate
 * @property int $index
 * @property string $task
 * @property bool $leavesTombstone
 */
class ChecklistItem extends ThingsModel
{
    use WithDefaultOrder, HasStatus;

    protected function timestamps(): array
    {
        return ['stopDate'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task');
    }
}
