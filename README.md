# Things3 — Trello sync

This tool lets you sync tasks from [Things](https://culturedcode.com/things/) to [Trello](https://trello.com).

The most basic use case — if you're a Trello user — is seeing your Things tasks in Trello, where you can collaborate with others, organize the tasks in different ways, and mix them with other (non-Things) tasks.

Additionally (and more importantly): Trello has a huge number of integrations, while Things has practically zero. This means that you can integrate **many** apps with Things using Trello as the middle-man.

My personal use case for building this was importing tasks from Things into [Sunsama](https://sunsama.com/). Sunsama is the personal task manager for my *days*. Things is the task manager for my *projects*. And this integration lets me pull in tasks from Things into Sunsama, without any gimmicks.

In this setup, Trello acts as a "portal" that lets me access my (huge) Things backlog right from Sunsama.

## Features

The features include:
- Syncing task name, description, completion status
- Subtasks, including their completion status
- Syncing Things tags as Trello labels
- Ability map Things *Headings*, *Projects, and *Areas* to Trello *Lists*, *Boards*, and *Workspaces*
- Selectively syncing tasks using a tag (directly on the task or on its *Heading*, *Project*, or *Area*)

The primary purpose of this tool is to replicate your Things tasks on Trello. The sync is one-directional which means that changes made on Trello will not be downloaded back into Things.

The synced task properties include: the title, description, subtasks (including completion status), due date, completion status, and optionally tags. If these get changed in Things, they will also be updated on Trello. This means that you shouldn't be changing these on Trello as your changes will be overwritten by the data from Things.

You **can** make changes on Trello, but they should be things like the assignee, comments, tags, and other "extra" information that doesn't directly change the information that's also present in Things.

You can also update task **status** from Trello, but there are some details to keep in mind. See the details [here](#pulling-state-from-trello).

## Requirements

- macOS
- PHP 8.1
- [composer](https://getcomposer.org/)

## Installation

### Local setup

First, install the composer dependencies:
```sh
composer install
```

Then, copy `.env.example` to `.env`:
```sh
cp .env.example .env
```

After that, test that the application can access your Things3 database. Running `php artisan things:list --projects` should list all of your projects.

If that worked and you don't see any errors, you can move on to the rest of the installation: configure the environment variables, try the manual sync, and optionally tweak the configuration and set up automatic sync.

If it failed, try changing the `DB_DATABASE` environment variable in `.env` to include your username instead of `{$USER}`. There's also an [official guide](https://culturedcode.com/things/support/articles/2982272/) for finding the database path.

## Configuration

This section explains how the app is configured.

All configuration files (`config/things.php` and `config/trello.php`) also have extensive documentation right next to the config keys.

### Environment variables

The `.env` file contains **environment variables**. The important ones include:
- `TRELLO_KEY` — your Trello API key. You can generate it [here](https://trello.com/app-key)
- `TRELLO_TOKEN` — when generating the key on the above link, also generate a token (think of the token as the "password" for the key)
- `TRELLO_BOARD` — name of the board where tasks will go by default. Example: `Things`
- `TRELLO_LIST` - name of the default list. Example: `Inbox`
- `TRELLO_WORKSPACE` — ID of the default workspace. Grab it by [opening the workspace settings](https://github.com/stancl/things3-trello-sync/raw/master/docs/show-workspace.png) and copying the ID from the URL (`https://trello.com/{{ YOUR WORKSPACE ID }}/account`)
- `THINGS_TAG` — name of the tag used in Things (it needs to already exist, so apply it on some task) indicating that the task should be synced into Trello. This variable is **optional**

Here's how it looks filled out (with sample values):
```
TRELLO_KEY=12345678912345678912345678912345
TRELLO_TOKEN=1234567891234567891234567891234512345678912345678912345678912345
TRELLO_BOARD=Things
TRELLO_LIST=Inbox
TRELLO_WORKSPACE=personal12345678
THINGS_TAG=Synced
```

This config will sync every Things task with the `Synced` tag into Trello (with board/list details explained below).

### Trello config

> These options can be found in `config/trello.php`

Things has *headings* inside *projects* inside *areas*. The `list`, `board`, and `workspace` config keys let you configure how this hierarchy is managed on Trello.

#### List config

- `things` = what represents lists in Things? By default this is `heading`
- `map` = rename things on Trello, e.g. `'Content' => 'Marketing'` to use the `Marketing` list instead of `Content`. If `list.things` is `heading`, this means that the `Content` *heading* will be stored as a `Marketing` list
- `only_mapped` = only create lists that are included in the `map`. Can be `true` or `false`
- `default` = the name of the default list. This comes from the `TRELLO_LIST` [environment variable](#environment-variables)

#### Board config

- `things` = what represents boards in Things? By default this is `project`
- `map` = rename things on Trello, e.g. `'Courses' => 'Reading'` to use the `Reading` board instead of `Content`. If `board.things` is `project`, this means that the `Content` *project* will be stored as a `Marketing` board
- `only_mapped` = only create boards that are included in the `map`. Can be `true` or `false`
- `default` = the name of the default board. This comes from the `TRELLO_BOARD` [environment variable](#environment-variables)

#### Workspace config

Important: **workspaces have *only_mapped* set to true implicitly. They NEED to be mapped to be synced**

- `things` = what represents workspaces in Things? By default this is **nothing** because only a single workspace is used. But if you'd like to use multiple workspaces, `area` is a good thing to separate them by
- `map` = map (area) names to workspace IDs
- `default` = the ID of the default workspace. This comes from the `TRELLO_WORKSPACE` [environment variable](#environment-variables)

#### Board status config

The `board_statuses` key lets you define board-specific Things status config.

In other words, the stuff you'd be configuring in `things.statuses` (`config/things.php`) can be defined on a board-specific basis.

A good example, for a `Movies` board, you may want to rename the incomplete/to-do list to `To Watch` and the complete/done list to `Watched`. You'd configure that like this:

```php
'board_statuses' => [
    'Movies' => [
        TaskStatus::INCOMPLETE() => [
            'create' => ['list' => 'To Watch'],
            'update' => ['list' => 'To Watch'],
        ],
        TaskStatus::COMPLETED() => [
            'update' => ['list' => 'Watched'],
        ],
    ],
],
```

### Things config

> These options can be found in `config/trello.php`

#### Things tag

The `things.tag` key specifies which Things tag should control whether a task gets synced with Trello.

If this value is filled, tasks will be synced with Trello if they:
1. have this tag directly, **or**
1. belong to a Project with this tag, **or**
1. belong to an Area with this tag.

If the value is `null`, **all** Things tasks will be synced on Trello.

#### Status config

The `things.statuses` key lets you configure how statuses of Things tasks should be managed on Trello.

Each status (e.g. `TaskStatus::INCOMPLETE()`) has two main options: `create` and `update`. These specify **how the task should be synced**.

For example, if the `create` of `COMPLETED` is `false`, tasks that are already completed will not be created on Things. This is usually the desired behavior because you likely don't care about the massive list of your already finished tasks.

On the other hand, `update` for `COMPLETED` is typically set to `true` because you *do* want to update tasks after/when they're completed.

The `create`/`update` essentially refers to the existence of *Trello cards*. If a card for the task already exists, the `update` config is used. If the card doesn't exist at all, the `create` config is what matters.

Aside from `true` and `false`, the values of these two keys can be **arrays with additional details**. These details can be `list` and `archive`.

The `list` setting is used like this:
```php
'create' => ['list' => 'To-do'],
```

This will create the tasks in a list called `To-do`.

The `archive` setting can be `true` or `false` and it indicates **if a task should be archived on Trello when it reaches this status on Things**. We use this with the `CANCELED` tasks by default:
```php
TaskStatus::CANCELED() => [
    'create' => false,
    'update' => ['archive' => true],
],
```

Already canceled tasks are not synced, just like already completed tasks aren't usually synced. And when tasks reach this status, their existing cards (`update`) are archived (`'archive' => true`).

If you'd like to configure these statuses on a board-specific basis, see the [Board status config](#board-status-config) section.

The purpose of the `when` key is convered in the [next section](#pulling-state-from-trello).

### Pulling state from Trello

One thing that *can* be pulled from Trello into Things is the task **status**.

For example, if you have a list for incomplete tasks and a separate list for completed tasks, the act of **moving a task from the incomplete list to the completed list** can mark the task as done in Things.

Some things to keep in mind:
- Changes in Things take precedence. If you move the task to a different list in Trello, but then make changes in Things before the Trello changes are pulled in, the task will go with the Things version and keep the old status. The deciding factor is **where was the task updated more recently**.
- The changes won't be immediately reflected in your Things app. You'll need to restart it (if it was running) to make the UI load data from the database file again.

Here's an example config (for `config/things.php`, `statuses` key):
```php
TaskStatus::INCOMPLETE() => [
    'create' => ['list' => 'To-do'], // Create new cards in the 'To-do' list
    'update' => true,
    'when' => ['list' => 'To-do'], // Change the local Things status to incomplete when the list is changed to 'To-do'
],
TaskStatus::COMPLETED() => [
    'create' => false,
    'update' => ['list' => 'Done'], // Change the card status to 'Done' when it's marked as completed in Things
    'when' => ['list' => 'Done'], // Change the local Things status to completed when it's moved to the 'Done' list
],
```

This will keep the complete & incomplete status in sync using the `To-do` and `Done` lists, bidirectionally.

## Syncing tasks

Tasks can be synced manually or automatically.

### Manual sync

To sync tasks manually, use the following command:
```php
php artisan things:sync
```

The command intelligently detects changes and doesn't make unnecessary API calls. **It also ignores changes older than the last 5 minutes**, so it's meant to be run regularly (see the section below).

To customize the interval, you can use the `--minutes` argument. For example, to sync updates over the past 10 minutes instead of 5, run this:
```php
php artisan things:sync --minutes 10
```

And if you'd like forcibly sync **all** changes, and not just the recent ones, you can use the `--force` option:
```php
php artisan things:sync --force
```

### Automatic sync

To sync your tasks automatically in the background, you can schedule the `things:sync` command to run every X minutes using crontab.

For example, to sync recently changed tasks every minute, add the following record to your crontab*:
```
*/1 * * * * php /path/to/the/sync/tool/artisan things:sync
```

To run the command every two minutes, the `*/1` becomes `*/2`.

5 minutes is not recommended since the few second difference between the default 5 minute limit and the schedule that crontab runs on could result in some tasks being missed.

*\ Run `EDITOR=nano crontab -e` to open the crontab settings. Use `ctrl+o+[enter]` to save the file afterwards. `ctrl+x` to exit.

#### Laravel scheduler

The better approach to syncing the tasks in the background is to use [Laravel's scheduler](https://laravel.com/docs/9.x/scheduling).

With this approach, [we schedule the `php artisan schedule:run` command to run every single minute](https://laravel.com/docs/9.x/scheduling#running-the-scheduler). And then we define the actual commands in `app/Console/Kernel.php`.

That way we don't have to touch `crontab` ever again and we get to use Laravel's (better) syntax for the scheduling logic.

The default schedule looks like this:
```php
$schedule->command('things:sync')->everyMinute();
$schedule->command('things:sync --force')->everyFifteenMinutes();
```

As the code suggests, this runs `things:sync` (latest changes) every minute, and `things:sync --force` every fifteen minutes, in case something wasn't synced due to e.g. the computer being offline.

### Extra options

The `things:sync` command also accepts the following arguments:
- `--filter=foo` to only sync tasks with `foo` in their name
- `--limit=20` to sync 20 tasks at most
- `--stats` to show all API calls and measurements after running. This is useful in development for diagnosing duplicate API calls

## Defaults and Common Patterns

### Defaults

The default configuration is opinionated, in a way that will be useful to most users. We try to follow *Convention over configuration*.

Here are the most relevant settings. You can change all of them, usually in the configuration files: `config/things.php` and `config/trello.php`.
1. **Canceled tasks don't get imported.** The app only syncs tasks that *become* canceled after already being on Trello.
1. **Tasks that get canceled get archived.** The app archives Trello cards of tasks that get canceled.
1. **Completed tasks don't get imported.** The app ignores the backlog of completed tasks (*Logbook* in Things) as it's likely huge and not important.
1. **Only one workspace is used.** The app creates all boards and lists within the default workspace.
1. ***Projects* are *Boards*.** The app maps Things *Projects* into Trello *Boards*.
1. ***Headings* are *Lists*.** The app maps Things *Headings* into Trello *Lists*.
1. Statuses don't have any lists associated with them.

### Common Patterns

Here are some common practices that you may choose to follow.

1. **Tags**: I recommend manually adding a tag to the tasks/projects/workspaces you want to sync (the `THINGS_TAG` [environment variable](#environment-variables)), rather than syncing absolutely everything.
1. **Inbox list**: replace `'create' => true` with `'create' => ['list' => 'Inbox']` for all statuses in the config. This makes anything **newly added** go into an *Inbox* list. From there, you can manually move them to your own lists as needed.
1. **Status lists**: create two lists: `To-do` and `Done`. Then, configure the two statuses like this:
    ```php
    TaskStatus::INCOMPLETE() => [
        'create' => ['list' => 'To-do'],
        'update' => true,
    ],
    TaskStatus::COMPLETED() => [
        'create' => false,
        'update' => ['list' => 'Done'],
    ],
    ```

    This creates all **new** incomplete tasks in the `To-do` list. From there, you can move it to **your own** `Work in progress` list (or any other list). And once the task gets completed, it will get automatically moved to the `Done` list.
1. **Canceled list**: you may want to create a separate list for tasks that get canceled — rather than archiving the cards. To do that, create the list, name it e.g. `Invalid`, and configure the status like this:
    ```php
    TaskStatus::CANCELED->value => [
        'create' => false,
        'update' => ['list' => 'Invalid'],
    ],
    ```
    This moves tasks that get canceled *after* being created on Trello to the `Invalid` list.
1. **Multiple workspaces**: if you want to use multiple Trello *Workspaces*, it's recommended that you use Things *Areas* for that. This way, you'll have: tasks inside headings (lists) inside projects (boards) inside areas (workspaces).
    ```php
    // config/trello.php
    'workspace' => [
        'things' => 'area',
        'map' => [
            // Workspaces are mapped to IDs
            'ArchTech' => 'archtech12',
        ],
        'default' => env('TRELLO_WORKSPACE'),
    ],
    ```
    Note that workspaces, unlike lists and boards, are mapped to IDs — not names. So you'll need to grab the ID from the workspace's URL.

## Support

- Support questions go into [Discussions -> Support](https://github.com/stancl/things3-trello-sync/discussions/categories/support)
- Feature suggestions and **non**-support questions go into [Discussions -> General](https://github.com/stancl/things3-trello-sync/discussions/categories/general)
- Bug reports go into [Issues](https://github.com/stancl/things3-trello-sync/issues)

If you'd like help with setting this up locally, email me at [samuel@archte.ch](mailto:samuel@archte.ch?subject=Things3%20Sync%20Setup).

## Technical details

- You can view the meaningful code (without the Laravel boilerplate & docs) by [diffing the third commit with HEAD](https://github.com/stancl/things3-trello-sync/compare/970373380e96cced75ea46810489a632c055e0ba...HEAD)
- We use static properties for caching Trello responses, so running the app as a long-running process could result in issues. For that reason, you should use `schedule:run` — the one-time process — executed using cron, rather than `schedule:work`.
