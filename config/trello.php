<?php

use App\Enums\TaskStatus;

return [
    'auth' => [
        'key' => env('TRELLO_KEY'),
        'token' => env('TRELLO_TOKEN'),
    ],

    /**
     * The 'workspace', 'board', and 'list' properties can be used to map Things categories to Trello categories.
     *
     * @property `things` The category in things. This can be `area`, `project`, and `heading`.
     *      @example `'things' => null` If you wish to disable this logic, and instead use the same
     *                                  list/board/workspace for all tasks, set the `things` property
     *                                  to null. Then, the app will use the `default` value for all tasks
     * @property `default` The default value. Used when a task doesn't have a area/project/heading.
     *
     * @property `map` Mapping overrides. These let you rename the final Trello categories.
     *      @example `'Reading' => 'Books'` will map the `Reading` in Things to `Books` in Trello.
     *
     * @property `only_mapped` This *disables* category mapping for anything that's not present in `map`.
     *      @example `'only_mapped' => true` Things categories not listed in `map` will use the default value.
     *      @example `'only_mapped' => false` Each Things category will have a Trello category with the same name.
     *                                        The `map` will only be used to *override* this mapping, e.g. rename projects.
     */

    'list' => [
        'things' => 'heading',
        'map' => [
            // Lists are mapped to names. If you specify a list that doesn't exist yet, it will be created
            // 'Content' => 'Marketing',
            // 'Design' => 'Branding',
            // 'Coding' => 'Development',
        ],
        'only_mapped' => false,
        'default' => env('TRELLO_LIST'),
    ],

    'board' => [
        'things' => 'project',
        'map' => [
            // Boards are mapped to names. If you specify a board that doesn't exist yet, it will be created
            // 'Courses' => 'Reading',
        ],
        'only_mapped' => false,
        'default' => env('TRELLO_BOARD'),
    ],

    'workspace' => [
        /**
         * Workspaces are configured slightly differently.
         *
         * @property `map` Things categories are mapped to Trello workspace *IDs*, rather than *names*.
         * @property `only_mapped = true` Workspaces *require* mapping. Otherwise the default workspace is used.
         */

        // 'things' => 'area', // Uncomment this if you want to enable multiple workspaces
        // 'map' => [
        //     'ArchTech' => 'archtech12',
        // ],

        // If we don't need workspaces, we only set the default
        // value to put everything in the personal workspace.
        'default' => env('TRELLO_WORKSPACE'),
    ],

    /**
     * Board-specific status configuration.
     *
     * This config can override the global status config (things.php -> statuses) on a board-specific basis.
     * This is useful if you want to use different list names in different boards (e.g. 'To Watch' instead of 'To-do').
     * You can also use this to customize the overall status *behavior* for this board. For example, enable creation even
     * for already completed tasks. Or, another example: instead of archiving canceled tasks, move them to a dedicated list.
     */
    'board_statuses' => [
        // 'Courses' => [
        //     TaskStatus::INCOMPLETE() => [
        //         'create' => ['list' => 'To Watch'],
        //         'update' => ['list' => 'To Watch'],
        //     ],
        //     TaskStatus::COMPLETED() => [
        //         'update' => ['list' => 'Watched'],
        //     ],
        // ]

        // 'Ideas' => [
        //     TaskStatus::CANCELED() => [
        //         'create' => ['list' => 'Rejected'],
        //         'update' => ['list' => 'Rejected'],
        //     ],
        // ],
    ],
];
