<?php

namespace App\Console\Commands;

use App\Models\Task;

use Illuminate\Support\Str;
use function Termwind\render;

class ListTasks extends Command
{
    protected $signature = 'things:tasks {--with-trashed}';

    public function handle()
    {
        $tasks = Task::all();

        $withDue = $tasks->whereNotNull('dueDate')->isNotEmpty();
        $withTrashed = $tasks->where('trashed', true)->isNotEmpty();

        render($this->makeTable(collect([
            ['Task'],
            ['Status'],
            $withDue ? ['Task due'] : [],
            $withTrashed ? ['Trashed'] : [],
            ['Description'],
        ])->flatten(), $tasks->map(fn (Task $task) => collect([
            [$task->title],
            [$task->taskStatus->toIcon() . ' ' . ucfirst($task->taskStatus->toText())],
            $withDue ? [$task->dueDate?->dueDiff()] : [],
            $withTrashed ? [$task->trashed ? '🗑' : ''] : [],
            [Str::limit($task->notes, 64)],
        ])->flatten())));
    }
}
