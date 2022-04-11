<?php

namespace App\Models\Concerns;

use App\Trello\Label;
use App\Enums\LabelColor;
use App\Trello\Board;

/**
 * @property-read string|null $label_color
 * @property-read string|null $trello_label_id
 *
 * @mixin \App\Models\Tag
 */
trait WithTrelloLabels
{
    /** The configured Trello label color. */
    public function getLabelColorAttribute(): LabelColor|null
    {
        $color = config('things.tags')[$this->title];

        return $color instanceof LabelColor ? $color : null;
    }

    public function getTrelloLabelIdAttribute(): string|null
    {
        $label = config('things.tags')[$this->title];

        return is_string($label) ? $label : null;
    }

    public function toLabel(Board|string $board): Label
    {
        return Label::forTag($this, $board);
    }
}
