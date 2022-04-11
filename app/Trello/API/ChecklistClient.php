<?php

namespace App\Trello\API;

use App\Trello\Card;
use App\Trello\Checkitem;
use App\Trello\Checklist;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Trello\Client
 */
trait ChecklistClient
{
    public static function createChecklist(Card $card): Checklist
    {
        return new Checklist(static::client()->post('checklists', [
            'idCard' => $card->id,
            ...static::auth(),
        ])->json());
    }

    public static function deleteChecklist(Checklist|string $checklist): bool
    {
        $checklist = $checklist instanceof Checklist ? $checklist->id : $checklist;

        return static::client()->delete("checklists/{$checklist}", static::auth())->successful();
    }

    public static function createCheckitem(Checklist $checklist, string $name, bool $checked = false): Checkitem
    {
        return new Checkitem(static::client()->post("checklists/{$checklist->id}/checkItems", [
            'name' => $name,
            'checked' => $checked,
            ...static::auth(),
        ])->json());
    }

    /** @return Collection<Checkitem> */
    public static function getCheckitems(Checklist|string $checklist): Collection
    {
        $checklist = $checklist instanceof Checklist
            ? $checklist->id
            : $checklist;

        return collect(static::client()->get('checklists/' . $checklist . '/checkItems', static::auth())->json())->map(fn (array $checkitem) => new Checkitem($checkitem));
    }
}
