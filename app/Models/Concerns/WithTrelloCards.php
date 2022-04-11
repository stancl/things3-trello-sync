<?php

namespace App\Models\Concerns;

use App\Models\Task;
use App\Trello\BoardList;
use App\Trello\Card;
use App\Trello\Client;
use Exception;
use Illuminate\Support\Carbon;

/**
 * @mixin Task
 */
trait WithTrelloCards
{
    public function toCard(): ?Card
    {
        return $this->findCard() ?? Client::createCard($this);
    }

    public function findCard(): ?Card
    {
        return Card::forTask($this);
    }

    public function createOnTrello(): Card
    {
        return Client::createCard($this);
    }

    public function updateOrCreateOnTrello(Card $card = null): ?Card
    {
        if ($card ??= $this->findCard()) {
            return $this->updateOnTrello($card);
        } else {
            return Client::createCard($this);
        }
    }

    public function updateOnTrello(Card $card = null): Card
    {
        $card ??= $this->findCard();

        if (! $card) {
            throw new Exception("Task {$this->uuid} has no card. Create it first via createOnTrello() or use updateOrCreateOnTrello().");
        }

        // If the task was updated more recently on Trello than in Things, we'll prioritize its (status) changes
        if (Carbon::parse($card->dateLastActivity)->isAfter($this->userModificationDate)) {
            $newStatus = collect($this->boardStatusConfig())->where('when.list', Client::listName($card->idList, $card->idBoard))->keys()->first();

            if ($newStatus !== null && $this->status !== $newStatus) {
                $this->update(['status' => $newStatus]);
            }
        }

        $card = Client::updateCard($card, $this);

        return $card;
    }

    public function deleteOnTrello(Card $card = null): bool
    {
        if ($card ??= $this->findCard()) {
            return Client::deleteCard($card);
        }

        return false;
    }
}
