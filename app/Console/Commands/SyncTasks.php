<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Trello\Client;
use Illuminate\Support\Str;

use function Termwind\render;
use Illuminate\Database\Eloquent\Builder;

class SyncTasks extends Command
{
    protected $signature = 'things:sync {--force} {--filter=} {--limit=} {--minutes=} {--stats}';

    public function handle()
    {
        $query = Task::query()
            ->orderBy('userModificationDate', 'desc')
            ->when($limit = $this->option('limit'), fn (Builder $query) => $query->limit($limit))
            ->when($filter = $this->option('filter'),
                fn (Builder $query) => $query->where('title', 'LIKE', "%{$filter}%")->orWhere('uuid', 'LIKE', "{$filter}%")
            );

        if ($this->option('force')) {
            // Force update all synced tasks
            $tasks = $query->cursor();

            render("Syncing <span class='text-red-500 font-bold'>all</span> (<span class='text-yellow-500'>{$tasks->count()}</span>) tasks");
        } else {
            // Only update tasks that were modified in the last 5 minutes
            $tasks = $query->where('userModificationDate', '>', now()->subMinutes($this->option('minutes') ?? 5)->timestamp)->cursor();

            render("Syncing <span class='text-green-500'>recently changed</span> (<span class='text-yellow-500'>{$tasks->count()}</span>) tasks");
        }

        $tasks->each(function (Task $task) {
            $original = $task->findCard();
            $new = $task->updateOrCreateOnTrello();

            if (! $new) {
                // Shouldn't be created
                render("<span class='text-gray-500'>Skipped task <a href='things:///show?id={$task->uuid}'>{$task->uuid}</a> (<span class='font-bold'>{$task->title}</span>)</span>");
            } else if (! $original) {
                // Didn't exist
                render("<span class='text-green-500'>Created task <a href='things:///show?id={$task->uuid}'>{$task->uuid}</a> (<a class='font-bold' href='https://trello.com/c/{$new->id}'>{$task->title}</a>)</span>");
            } else if ($new->recentlyChangedChildren || ! $new->equals($original)) {
                // Was outdated
                render("<span class='text-yellow-500'>Updated task <a href='things:///show?id={$task->uuid}'>{$task->uuid}</a> (<a class='font-bold' href='https://trello.com/c/{$new->id}'>{$task->title}</a>)</span>");
            } else {
                // Up to date
                render("<span class='text-blue-500'>Checked task <a href='things:///show?id={$task->uuid}'>{$task->uuid}</a> (<a class='font-bold' href='https://trello.com/c/{$new->id}'>{$task->title}</a>)</span>");
            }
        });

        render("Synced <span class='text-green-500'>{$tasks->count()} tasks</span>");

        if ($this->option('stats')) {
            render("<span class='font-bold'>Stats:</span>");

            $number = 1;

            render($this->makeTable(
                headings: ['#', 'Method', 'URL', 'Duplicate #'],
                rows: collect(Client::$log)->map(function (string $url) use (&$number) {
                    [$method, $path] = explode(' ', $url);
                    $count = collect(Client::$log)->filter(fn (string $u) => $u === $url)->count();

                    return [
                        'number' => $number++,
                        'method' => [$method === 'GET' ? 'text-red-500' : 'text-green-500', $method],
                        'path' => $path,
                        'count' => $count > 1 ? ['text-red-500', $count] : $count,
                    ];
                })->toArray(),
            ));
        }
    }
}
