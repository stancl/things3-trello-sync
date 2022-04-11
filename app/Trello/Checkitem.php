<?php

namespace App\Trello;

/**
 * @property string $id
 * @property string $idChecklist
 * @property string $name
 * @property array $nameData
 * @property mixed $pos
 * @property string $state
 * @property mixed $due
 * @property mixed $idMember
 */
class Checkitem extends APIResource
{
    public function isCompleted(): bool
    {
        return $this->state === 'complete';
    }
}
