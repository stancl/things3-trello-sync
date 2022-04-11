<?php

namespace App\Trello\API;

use App\Models\Tag;
use App\Models\Task;
use App\Trello\Board;
use App\Trello\Card;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Trello\Client
 */
trait CardClient
{
    public static array $taskCardCache = [
        // task_id => card_id
    ];

    public static array $cardCache = [
        // card_id => Card
    ];

    protected static array $boardCardCache = [
        // board_id => ['card1', 'card2']
    ];

    public static function createCard(Task $task): Card|null
    {
        $data = static::applyStatusConfig($task, 'create');

        if ($data === null) {
            return null;
        }

        $data['idList'] ??= $task->targetList();

        return static::makeCard(static::client()->post('cards', [
            ...static::syncedTaskData($task),
            ...static::auth(),
            ...$data,
        ])->json(), $task);
    }

    public static function updateCard(Card $card, Task $task): Card
    {
        $data = static::applyStatusConfig($task, 'update');

        $data['idList'] ??= $task->targetList();

        if ($data === null) {
            return $card;
        }

        return static::makeCard(static::client()->put("cards/{$card->id}", [
            ...static::syncedTaskData($task),
            ...static::auth(),
            ...$data,
        ])->json(), $task);
    }

    protected static function applyStatusConfig(Task $task, string $action, array $data = [], Card $card = null): ?array
    {
        $config = $task->statusConfig()[$action]; // ['list' => ...]

        if ($config === false) {
            return null;
        }

        if (isset($config['list'])) {
            $data['idList'] = static::listID($config['list'], $task->targetBoard());
        }

        if (isset($config['archive'])) {
            $data['closed'] = $config['archive'];

            if ($card?->closed && $config['archive']) {
                // If we're archiving an already archived card, we can skip the request
                return null;
            }
        }

        return $data;
    }

    public static function deleteCard(Card $card): bool
    {
        return tap(static::client()->delete("cards/{$card->id}", static::auth())->successful(), function (bool $success) use ($card) {
            if ($success) {
                unset(static::$cardCache[$card->id]);
                static::$taskCardCache = collect(static::$taskCardCache)->flip()->except($card->id)->flip()->toArray();
            }
        });
    }

    /** @return Collection<Card> */
    public static function getCards(Board|string $board): Collection
    {
        $board = (string) $board;

        if (isset(static::$boardCardCache[$board])) {
            $result = collect(static::$boardCardCache[$board])->map(fn (string $boardID) => static::$cardCache[$boardID] ?? null);

            if ($result->doesntContain(null)) {
                // All boards were found
                return $result;
            }
        }

        return collect(static::client()->get("/boards/{$board}/cards", static::auth())->json())
            ->filter(fn (array $card) => Str::of($card['desc'])->contains('things:///show?id='))
            ->map(fn (array $card) => new Card($card))
            ->tap(fn (Collection $cards) => static::$boardCardCache[$board] = $cards->pluck('id')->all())
            ->each(function (Card $card) {
                $card->getTaskUUID(); // cache task id => card id mapping
                static::$cardCache[$card->id] = $card; // cache Card instance
            });
    }

    /** @return Collection<Task> */
    public static function getTasks(Board|string $board): Collection
    {
        return static::getCards($board)->map->toTask();
    }

    protected static function syncedTaskData(Task $task): array
    {
        return [
            ...$task->syncedData(),
            'desc' => "{$task->notes}\n\n---- Don't edit below this line ----\n**[View original task in Things](things:///show?id={$task->uuid})**",
        ];
    }

    protected static function makeCard(array $data, Task $task): Card
    {
        return tap(new Card($data), function (Card $card) use ($task) {
            // Add or sync checklist
            if ($task->children) {
                $card->withChecklist($task->children->pluck('complete', 'title')->toArray());
            } else {
                // Remove checklist if present
                $card->withoutChecklist();
            }

            if ($tags = $task->syncedTags()) {
                // Sync Things tags with Trello labels
                $card->withLabels($tags->map(fn (Tag $tag) => $tag->toLabel($task->targetBoard()))->toArray());
            }

            static::$taskCardCache[$task->uuid] = $card->id;
            static::$cardCache[$card->id] = $card;
        });
    }
}
