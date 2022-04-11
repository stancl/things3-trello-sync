<?php

use App\Enums\LabelColor;
use App\Enums\TaskStatus;

return [
    /**
     * Which Things tag should control whether a task gets synced with Trello.
     *
     * If this value is filled, tasks that:
     *      1) have this tag directly
     *      2) belong to a Project with this tag
     *      3) belong to an Area with this tag
     * will be synced with Trello.
     *
     * @example `'Trello'` Sync all tasks that have the 'Trello' tag with Trello.
     * @example `'Any other tag'` Sync all tasks that have the 'Any other tag' tag with Trello.
     * @example `null` Sync all tasks with trello
     */
    'tag' => env('THINGS_TAG'),

    /**
     * How should tasks be treated when they have a certain status.
     *
     * The `create` property defines what happens with tasks with this status that are NOT on Trello yet.
     *   @example `true` Create the task on Trello
     *   @example `false` Don't create the task on Trello
     *
     * The `update` property defines what happens with tasks that ARE already on Trello, when they reach this status.
     *   @example `true` Update the Trello task
     *   @example `false` Don't update the Trello task
     *
     * Additionally, you may choose to move a task to a certain list or archive it when it has a certain status.
     *   @example `['list' => 'Done']` Create the task in the 'Done' list, or move it to that list
     *   @example `['archive' => true]` Mark the task as archived. If it's already archived, changes won't be synced.
     *
     * If no list is provided, the default one will be used for new cards. Existing cards won't be moved.
     *   @see config/trello.php -> list -> default
     */
    'statuses' => [
        TaskStatus::INCOMPLETE() => [
            'create' => true, // ['list' => 'To-do']
            'update' => true,
            // 'when' => ['list' => 'To-do'],
        ],
        TaskStatus::COMPLETED() => [
            'create' => false,
            'update' => true, // ['list' => 'Done']
            // 'when' => ['list' => 'Done'],
        ],
        TaskStatus::CANCELED() => [
            'create' => false,
            'update' => ['archive' => true],
        ],
    ],

    /**
     * Mapping of synced properties: Things => Trello
     */
    'sync' => [
        // title & description are synced by default
        'dueDate' => 'due',
        'complete' => 'dueComplete',
        'trashed' => 'closed', // = archived
    ],

    'tags' => [
        'Bug' => LabelColor::RED, // Create a new Trello label with the provided color
        // 'Low priority' => LabelColor::BLUE,
        // 'Bug' => '6202e9dd4c593774a2d17bf0', // Use an existing Trello label with the provided ID
    ],
];
