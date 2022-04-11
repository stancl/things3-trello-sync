<?php

namespace App\Trello;

/**
 * @property string $id
 * @property string $name
 * @property string $desc
 * @property string $idOrganization
 */
class Board extends APIResource
{
    public static function firstOrCreate(Workspace|string $workspace, string $name): static
    {
        return Client::getBoards($workspace)->where('name', $name)->first() ?? Client::createBoard($workspace, $name);
    }
}
