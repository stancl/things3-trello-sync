<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;

/**
 * Task status used by Things tasks and checklist items
 */
enum TaskStatus: int
{
    use InvokableCases;

    case INCOMPLETE = 0;
    case CANCELED = 2;
    case COMPLETED = 3;

    public function isComplete(): bool
    {
        // Trello only has complete/incomplete, so we consider both completed & canceled as complete
        return $this !== TaskStatus::INCOMPLETE;
    }

    public function toText(): string
    {
        return match ($this) {
            TaskStatus::INCOMPLETE => 'incomplete',
            TaskStatus::CANCELED => 'canceled',
            TaskStatus::COMPLETED => 'completed',
        };
    }

    public function toIcon(): string
    {
        return match ($this) {
            TaskStatus::INCOMPLETE => '🟠',
            TaskStatus::CANCELED => '❌',
            TaskStatus::COMPLETED => '✅',
        };
    }

    public static function fromText(string $status): static
    {
        return static::from(static::{strtoupper($status)}());
    }
}
