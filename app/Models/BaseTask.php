<?php

namespace App\Models;

use App\Models\Concerns\WithDefaultOrder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Parental\HasChildren;

/**
 * @property bool trashed
 * @property bool type
 * @property string title
 * @property string notes
 * @property ?Carbon dueDate
 * @property int dueDateOffset
 * @property int status
 * @property ?Carbon stopDate
 * @property int start
 * @property ?Carbon startDate
 * @property int index
 * @property int todayIndex
 * @property string|null area parentArea() relation
 * @property string|null project parentProject() relation
 * @property mixed repeatingTemplate
 * @property mixed delegate
 * @property mixed recurrenceRule
 * @property ?Carbon instanceCreationStartDate
 * @property bool instanceCreationPaused
 * @property bool instanceCreationCount
 * @property ?Carbon afterCompletionReferenceDate
 * @property string|null actionGroup heading() relation
 * @property int untrashedLeafActionsCount
 * @property int openUntrashedLeafActionsCount
 * @property int checklistItemsCount
 * @property int openChecklistItemsCount
 * @property int startBucket
 * @property mixed alarmTimeOffset
 * @property ?Carbon lastAlarmInteractionDate
 * @property ?Carbon todayIndexReferenceDate
 * @property ?Carbon nextInstanceStartDate
 * @property ?Carbon dueDateSuppressionDate
 * @property bool leavesTombstone
 * @property mixed repeater
 * @property mixed repeaterMigrationDate
 * @property mixed repeaterRegularSlotDatesCache
 * @property bool notesSync
 * @property string|null cachedTags
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<Tag> $tags
 */
class BaseTask extends ThingsModel
{
    use WithDefaultOrder, HasChildren;

    protected $table = 'TMTask';
    protected $childColumn = 'type';

    protected $childTypes = [
        0 => Task::class,
        1 => Project::class,
        2 => Heading::class,
    ];

    protected $casts = [
        'trashed' => 'bool',
    ];

    protected function timestamps(): array
    {
        return [
            'startDate',
            'dueDate',
            'stopDate',
            'instanceCreationStartDate',
            'afterCompletionReferenceDate',
            'lastAlarmInteractionDate',
            'todayIndexReferenceDate',
            'nextInstanceStartDate',
            'dueDateSuppressionDate',
        ];
    }

    /** All of the task's tags. */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'TMTaskTag', 'tasks', 'tags');
    }

    /** Tags other than the one indicating sync. */
    public function extraTags(): Collection
    {
        if (config('things.tag')) {
            return $this->tags->toBase()->where('title', '!=', config('things.tag'));
        } else {
            return $this->tags->toBase();
        }
    }

    /** Tags other than the one indicating sync. */
    public function syncedTags(): Collection
    {
        return $this->extraTags()->whereIn('title', array_keys(config('things.tags')));
    }
}
