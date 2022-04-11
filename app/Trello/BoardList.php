<?php

namespace App\Trello;

/**
 * @property string $id
 * @property string $name
 * @property bool $closed
 * @property int $pos
 * @property string $idBoard
 */
class BoardList extends APIResource
{
    public static function firstOrCreate(Board|string $board, string $name): static
    {
        return Client::getLists($board)->where('name', $name)->first() ?? Client::createList($board, $name);
    }
}
