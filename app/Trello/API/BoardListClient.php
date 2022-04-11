<?php

namespace App\Trello\API;

use App\Trello\Board;
use App\Trello\BoardList;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Trello\Client
 */
trait BoardListClient
{
    protected static array $boardListCache = [
        // board_id => [BoardList, BoardList]
    ];

    /** @see https://developer.atlassian.com/cloud/trello/rest/api-group-boards/#api-boards-id-lists-post */
    public static function createList(Board|string $board, string $name): BoardList
    {
        $board = (string) $board;

        return tap(new BoardList(static::client()->post("boards/{$board}/lists", [
            'name' => $name,
            ...static::auth(),
        ])->json()), function () use ($board) {
            unset(static::$boardListCache[$board]);
        });
    }

    /**
     * @see https://developer.atlassian.com/cloud/trello/rest/api-group-boards/#api-boards-id-lists-get
     * @return Collection<BoardList>
     */
    public static function getLists(Board|string $board): Collection
    {
        $board = (string) $board;

        if (isset(static::$boardListCache[$board])) {
            return collect(static::$boardListCache[$board]);
        }

        return collect(static::client()->get("boards/{$board}/lists", static::auth())->json())
            ->map(fn (array $list) => new BoardList($list))
            ->tap(fn (Collection $lists) => static::$boardListCache[$board] = $lists->all());
    }

    /** Convert a list's name to an ID. */
    public static function listID(string $list, Board|string $board): string
    {
        return BoardList::firstOrCreate($board, $list)->id;
    }

    /** Get a list's name. */
    public static function listName(BoardList|string $list, Board|string $board): string|null
    {
        if ($list instanceof BoardList) {
            return $list->name;
        }

        return static::getLists($board)->firstWhere('id', $list)?->name;
    }
}
