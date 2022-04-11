<?php

namespace App\Models\Concerns;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $status Raw integer status
 * @property Carbon|null $stopDate The completion timestamp of the task
 * @property-read bool $complete Is the task completed?
 * @property-read TaskStatus $taskStatus
 */
trait HasStatus
{
    protected static function bootHasStatus(): void
    {
        static::saving(function (Model $model) {
            /** @var Model&self $model */

            if ($model->taskStatus->isComplete()) {
                $model->stopDate = now();
            } else {
                $model->stopDate = null;
            }
        });
    }

    protected function taskStatus(): Attribute
    {
        return new Attribute(
            get: fn () => TaskStatus::from($this->status),
        );
    }

    public function getCompleteAttribute(): bool
    {
        return $this->taskStatus->isComplete();
    }
}
