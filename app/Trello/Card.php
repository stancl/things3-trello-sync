<?php

namespace App\Trello;

use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property-read string id
 * @property-read mixed checkItemStates
 * @property-read bool closed
 * @property-read string dateLastActivity
 * @property-read string desc
 * @property-read array descData
 * @property-read mixed dueReminder
 * @property-read string idBoard
 * @property-read string idList
 * @property-read array idMembersVoted
 * @property-read int idShort
 * @property-read mixed idAttachmentCover
 * @property-read array idLabels
 * @property-read bool manualCoverAttachment
 * @property-read string name
 * @property-read int pos
 * @property-read string shortLink
 * @property-read bool isTemplate
 * @property-read mixed cardRole
 * @property-read array badges
 * @property-read bool dueComplete
 * @property-read mixed due
 * @property-read array idChecklists
 * @property-read array idMembers
 * @property-read array labels
 * @property-read string shortUrl
 * @property-read mixed start
 * @property-read bool subscribed
 * @property-read string url
 * @property-read array cover
 */
class Card extends APIResource
{
    public bool $recentlyChangedChildren = false;

    public static function forTask(Task $task): static|null
    {
        if (isset(Client::$taskCardCache[$task->uuid])) {
            // Check if the task id => card id mapping is cached
            if (isset(Client::$cardCache[Client::$taskCardCache[$task->uuid]])) {
                // Check if the card id => Card mapping is cached
                return Client::$cardCache[Client::$taskCardCache[$task->uuid]];
            }
        }

        /** @var ?Card $card */
        $card = Client::getCards($task->targetBoard())->filter(function (Card $card) use ($task) {
            return Str::of($card->desc)->contains('things:///show?id=' . $task->uuid);
        })->first();

        if ($card) {
            Client::$taskCardCache[$task->uuid] = $card->id;
        }

        return $card;
    }

    public function getTaskUUID(): string|null
    {
        $uuid = (string) Str::of($this->desc)->match('/\(things:\/\/\/show\?id\=(.*?)\)/');

        if ($uuid && strlen($uuid) === 22) {
            Client::$taskCardCache[$uuid] = $this->id;
        }

        return $uuid;
    }

    public function toTask(): Task|null
    {
        return $this->cache['task'] ??= Task::find($this->getTaskUUID());
    }

    public function withChecklist(array $items = []): static
    {
        if (! $items) {
            return $this;
        }

        if (! $this->idChecklists) {
            $checklist = Client::createChecklist($this);

            foreach ($items as $name => $completed) {
                Client::createCheckitem($checklist, $name, $completed);
            }

            $this->apiData['idChecklists'][] = $checklist->id;

            $this->recentlyChangedChildren = true;

            return $this;
        }

        $checklist = array_values($this->idChecklists)[0];

        $checkitems = Client::getCheckitems($checklist);
        $rawItems = $checkitems->sortBy('pos')->keyBy('name')->map(fn (Checkitem $item) => $item->isCompleted());

        if ($rawItems->toArray() !== $items) {
            return $this->withoutChecklist()->withChecklist($items);
        }

        return $this;
    }

    public function withoutChecklist(): static
    {
        foreach ($this->idChecklists as $checklist) {
            Client::deleteChecklist($checklist);

            $this->recentlyChangedChildren = true;
        }

        $this->apiData['idChecklists'] = [];

        return $this;
    }

    /**
     * @param Label[] $labels
     */
    public function withLabels(array $labels = []): static
    {
        $thingsLabels = collect($labels)->keyBy('id');
        $trelloLabels = collect($this->labels)->keyBy('id');

        $toKeep = $trelloLabels->whereIn('id', $thingsLabels->keys())->keys();

        $toRemove = $trelloLabels->except($toKeep)->keys();
        $toAdd = $thingsLabels->except($trelloLabels->keys())->keys();

        $toRemove->each(function (string $id) {
            Client::removeLabel($this, $id);

            $this->recentlyChangedChildren = true;
        });

        $toAdd->each(function (string $id) {
            Client::addLabel($this, $id);

            $this->recentlyChangedChildren = true;
        });

        return $this;
    }

    public function equals(Card $that): bool
    {
        if ($this->toArray() === $that->toArray()) {
            return true;
        }

        $extract = fn (Card $card) => collect($card->toArray())->only(array_keys($card->toTask()->syncedData()));

        return $extract($this)->diffAssoc($extract($that))->isEmpty();
    }
}
