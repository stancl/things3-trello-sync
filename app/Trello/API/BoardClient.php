<?php

namespace App\Trello\API;

use App\Trello\Board;
use App\Trello\Workspace;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Trello\Client
 */
trait BoardClient
{
    protected static array $workspaceBoardCache = [
        // 'workspace_id' => ['board1', 'board2']
    ];
    protected static array $boardCache = [
        // 'board_id' => $board1
    ];

    /**
     * @see https://developer.atlassian.com/cloud/trello/rest/api-group-organizations/#api-organizations-id-boards-get
     * @return Collection<Board>
     */
    public static function getBoards(Workspace|string $workspace): Collection
    {
        $workspace = (string) $workspace;

        if (isset(static::$workspaceBoardCache[$workspace])) {
            $result = collect(static::$workspaceBoardCache[$workspace])->map(fn (string $boardID) => static::$boardCache[$boardID] ?? null);

            if ($result->doesntContain(null)) {
                // All boards were found
                return $result;
            }
        }

        return collect(
            static::client()->get("organizations/{$workspace}/boards", [
                'filter' => 'open',
                ...static::auth(),
            ])->json(),
        )->map(fn (array $board) => new Board($board))->each(function (Board $board) {
            static::$boardCache[$board->id] = $board;
        })->tap(fn (Collection $boards) => static::$workspaceBoardCache[$workspace] = $boards->pluck('id')->all());
    }

    /** @see https://developer.atlassian.com/cloud/trello/rest/api-group-boards/#api-boards-post */
    public static function createBoard(Workspace|string $workspace, string $name): Board
    {
        $workspace = (string) $workspace;

        return tap(new Board(static::client()->post("boards", [
            'name' => $name,
            'idOrganization' => $workspace,
            ...static::auth(),
        ])->json()), function () use ($workspace) {
            unset(static::$workspaceBoardCache[$workspace]);
        });
    }
}
