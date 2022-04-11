<?php

namespace App\Models;

use App\Models\Concerns\{HasArea, HasProject, HasHeading, HasStatus, WithTrelloCards};
use App\Trello\Board;
use App\Trello\BoardList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Parental\HasParent;

/**
 * @property-read Collection<ChecklistItem> $children
 */
class Task extends BaseTask
{
    use WithTrelloCards, HasParent, HasProject, HasArea, HasHeading, HasStatus;

    public static function booted()
    {
        // Ignore trashed tasks
        static::addGlobalScope('withoutTrashed', function (Builder $query) {
            $query->where('trashed', false);
        });

        // Ignore tasks without the specified tag
        if (config('things.tag')) {
            static::addGlobalScope('tagged', function (Builder $query) {
                $query->where(function (Builder $query) {
                    // Checks if the model has the label
                    $tagScope = fn (Builder $tags) => $tags->where('title', config('things.tag'));

                    // Checks if the model's parent relation has the label
                    $parentScope = fn (Builder $parent) => $parent->withoutGlobalScopes()->whereHas('tags', $tagScope);

                    // Check if the model's parent project has the label OR belongs to an area with the label
                    $projectScope = fn (Builder $project) => $parentScope($project)->orWhereHas('parentArea', $tagScope);

                    // Checks if the model's parent heading has the label OR belongs
                    // to a project that does, or a project whose parent area does
                    $headingScope = fn (Builder $heading) => $heading->withoutGlobalScopes()->whereHas('parentProject', $projectScope);

                    return $query
                        ->whereHas('tags', $tagScope) // Task tags
                        ->orWhereHas('parentProject', $projectScope) // Direct parent project tags
                        ->orWhereHas('parentArea', $parentScope) // Direct parent area tags
                        ->orWhereHas('heading', $headingScope); // Direct parent heading nested parent tags
                });
            });
        }
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'task');
    }

    public function syncedData(): array
    {
        return [
            'name' => $this->title,
            'desc' => $this->notes,
            ...collect(config('things.sync'))
            ->flip()
                ->map(fn (string $attribute) => $this->getAttribute($attribute))
                ->map(fn ($value) => $value instanceof Carbon ? (string) $value : $value)
                ->toArray(),
        ];
    }

    public function targetList(string $property = 'id'): string
    {
        return BoardList::firstOrCreate($this->targetBoard(), $this->mapToTrello('list'))->$property;
    }

    public function targetBoard(string $property = 'id'): string
    {
        return Board::firstOrCreate($this->targetWorkspace(), $this->mapToTrello('board'))->$property;
    }

    public function targetWorkspace(): string
    {
        return $this->mapToTrello('workspace', onlyMapped: true);
    }

    protected function mapToTrello(string $category, bool $onlyMapped = null): string
    {
        $default = config("trello.{$category}.default");

        $title = match (config("trello.{$category}.things")) {
            'heading' => $this->heading?->title,
            'project' => $this->parentProject?->title ?? $this->heading?->parentProject->title,
            'area' => $this->parentArea?->title ?? $this->parentProject?->parentArea->title,
            default => null,
        } ?? $default;

        if ($title === $default) {
            return $title;
        }

        if ($onlyMapped ?? config("trello.{$category}.only_mapped", false)) {
            // Use the default value if this one isn't included in the map
            if (! array_key_exists($title, config("trello.{$category}.map", []))) {
                return $default;
            }
        } else {
            return config("trello.{$category}.map", [])[$title] ?? $title;
        }

        return $title;
    }

    public function statusConfig(): array
    {
        return $this->boardStatusConfig()[$this->status]; // ['create' => ..., 'update' => '...]
    }

    public function boardStatusConfig(): array
    {
        $statuses = collect(config('things.statuses'));

        if ($boardStatuses = config('trello.board_statuses')[$this->targetBoard('name')] ?? []) {
            // Apply board-specific overrides
            $statuses = $statuses->map(fn (array $default, int $status) => [
                'create' => data_get($boardStatuses, "{$status}.create", $default['create'] ?? false),
                'update' => data_get($boardStatuses, "{$status}.update", $default['update'] ?? false),
                'when' => data_get($boardStatuses, "{$status}.when", $default['when'] ?? null),
            ]);
        }

        return $statuses->all();
    }
}
