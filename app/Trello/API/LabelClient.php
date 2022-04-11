<?php

namespace App\Trello\API;

use App\Trello\Board;
use App\Trello\Card;
use App\Trello\Label;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Trello\Client
 */
trait LabelClient
{
    /** @return Collection<Label> */
    public static function getLabels(Board|string $board): Collection
    {
        return collect(static::client()->get("boards/{$board}/labels", [
            'fields' => ['name', 'color', 'idBoard'],
            ...static::auth(),
        ])->json())->where('name', '!=', '')->map(fn (array $label) => new Label($label));
    }

    public static function createLabel(Board|string $board, string $name, string $color): Label
    {
        return new Label(static::client()->post("boards/{$board}/labels", [
            'name' => $name,
            'color' => $color,
            ...static::auth(),
        ])->json());
    }

    public static function addLabel(Card $card, Label|string $label): bool
    {
        $label = $label instanceof Label ? $label->id : $label;

        return static::client()->post("cards/{$card->id}/idLabels", [
            'value' => $label,
            ...static::auth(),
        ])->successful();
    }

    public static function removeLabel(Card $card, Label|string $label): bool
    {
        $label = $label instanceof Label ? $label->id : $label;

        return static::client()->delete("cards/{$card->id}/idLabels/{$label}", static::auth())->successful();
    }
}
