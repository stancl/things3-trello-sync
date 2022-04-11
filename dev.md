# Dev notes

Moving a few tasks here as they don't need to be implemented now, but I want to have a list of the things I was considering during development.

- `art things:sync --recreate` which requires confirmation, and deletes the boards as well
- Allow having `Today` and `This evening` automatically moved to a specific board (the "day board", by default it can just be the default board)
- The logic used in `ListThings::renderProjects()` and similar methods doesn't fully respect the mapping details, such as the `only_mapped` config
    - The `mapToTrello()` method should likely be extracted to work for Projects and other `BaseTask` children as well
    - That's a bit more complex because methods like `targetBoard()` currently sometimes **initialize (e.g. list) creation on Trello**, but for purposes of the `things:list` command, we only want the names. We don't want to touch Trello yet
