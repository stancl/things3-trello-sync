<?php

namespace App\Trello;

use App\Models\Tag;
use App\Enums\LabelColor;

/**
 * @property string $id
 * @property string $idBoard
 * @property string $name
 * @property string $color
 */
class Label extends APIResource
{
    public static function firstOrCreate(Board|string $board, string $name, LabelColor $color): static
    {
        return Client::getLabels($board)->where('name', $name)->first() ?? Client::createLabel($board, $name, $color->value);
    }

    public static function forTag(Tag $tag, Board|string $board): static
    {
        if ($tag->label_color) {
            // We're using a custom label with the tag's name and the configured color
            return static::firstOrCreate($board, $tag->title, $tag->labelColor);
        }

        // We're using an existing label's ID
        return Client::getLabels($board)->where('id', $tag->trello_label_id)->first();
    }

    public function color(): LabelColor
    {
        return LabelColor::from($this->color);
    }
}
