<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Enums\LabelColor;
use App\Models\Area;
use App\Models\Heading;
use App\Models\Project;

use function Termwind\render;

class ListThings extends Command
{
    protected $signature = 'things:list {--tags} {--areas} {--projects} {--headings}';

    public function handle()
    {
        if ($this->option('tags')) {
            $this->renderTags();
        }

        if ($this->option('areas')) {
            $this->renderAreas();
        }

        if ($this->option('projects')) {
            $this->renderProjects();
        }

        if ($this->option('headings')) {
            $this->renderHeadings();
        }
    }

    protected function renderTags(): void
    {
        render($this->makeTable(
            headings: ['Title', 'UUID', 'Index', 'Trello'],
            rows: Tag::all()->map->only('title', 'uuid', 'index')->map(function (array $data) {
                if ($data['title'] === config('things.tag')) {
                    $data['_titleStyle'] = 'text-indigo-600';
                }

                if (array_key_exists($data['title'], config('things.tags'))) {
                    $trello = config('things.tags.' . $data['title']);

                    if ($trello instanceof LabelColor) {
                        $data['trello'] = ["text-{$trello->value}-700", $data['title']];
                    } else {
                        $data['trello'] = $trello;
                    }
                } else {
                    $data['trello'] = ['italic', 'Not synced'];
                }

                return $data;
            })->toArray(),
        ));
    }

    protected function renderAreas(): void
    {
        $trello = match ('area') {
            config('trello.workspace.things') => 'workspace',
            config('trello.board.things') => 'board',
            default => null,
        };

        render($this->makeTable(
            headings: ['Title', 'UUID', 'Synced', ...($trello ? ['Trello ' . ucfirst($trello)] : [])],
            rows: Area::all()->map->only('title', 'uuid')->map(function (array $data) use ($trello) {
                $area = Area::find($data['uuid']);

                if ($tag = config('things.tag')) {
                    $synced = !! $area->tags->firstWhere('title', $tag);
                } else {
                    $synced = true;
                }

                $data['synced'] = $synced ? 'Y' : 'N';

                if ($trello) {
                    $mappedTitle = config("trello.$trello.map.{$area->title}");
                    $trelloTitle = $mappedTitle ?? $area->title;

                    if ($trello === 'workspace') {
                        // Workspaces NEED to be mapped
                        $synced = $synced && $mappedTitle;
                    }

                    $data['trello'] = $synced ? $trelloTitle : ['italic', 'Default'];
                }

                return $data;
            })->toArray(),
        ));
    }

    protected function renderProjects(): void
    {
        $trello = match ('project') {
            config('trello.list.things') => 'list',
            config('trello.workspace.things') => 'workspace',
            config('trello.board.things') => 'board',
            default => null,
        };

        // Highlight Trello titles overridden by the map
        $title = fn (string|null $title, string $default) => $title ? ['text-red-600', $title] : $default;

        render($this->makeTable(
            headings: ['Title', 'UUID', 'Area', ...($trello ? ['Trello ' . ucfirst($trello)] : [])],
            rows: Project::where('status', 0)->get()->map(fn (Project $project) => collect([
                $project->title,
                $project->uuid,
                $project->parentArea?->title,
                $trello ? $title(config("trello.$trello.map.{$project->title}"), $project->title) : null,
            ])->filter()),
        ));
    }

    protected function renderHeadings(): void
    {
        $trello = match ('heading') {
            config('trello.list.things') => 'list',
            config('trello.workspace.things') => 'workspace',
            config('trello.board.things') => 'board',
            default => null,
        };

        // Highlight Trello titles overridden by the map
        $title = fn (string|null $title, string $default) => $title ? ['text-red-600', $title] : $default;

        render($this->makeTable(
            headings: ['Title', 'UUID', 'Area', ...($trello ? ['Trello ' . ucfirst($trello)] : [])],
            rows: Heading::where('status', 0)->get()->map(fn (Heading $heading) => collect([
                $heading->title,
                $heading->uuid,
                $heading->parentProject?->title,
                $trello ? $title(config("trello.$trello.map.{$heading->title}"), $heading->title) : null,
            ])->filter()),
        ));
    }
}
