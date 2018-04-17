# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [Github](https://github.com/BackEndTea/PHP-Optimize).

## Pull Requests

- Submit one Pull Request per fix or feature.
- Follow conventions used in the project.
- If your changes are not up to date, [rebase](https://git-scm.com/docs/git-rebase) your branch onto the parent branch.
- Add/Update tests and documentation.

If you are unsure on how to proceed with a PR, you are welcome to create a work in progress PR asking for help/feedback.

## Tooling
We use a `Makefile` for automated tasks, it has the following commands

- `make analyze` - Runs style checkers and static analysis tools, and validates `composer.json
- `make cs-fix` - Automatically fixes styling issues.
- `make test-unit` - Runs phpunit tests
- `make test-infection` - Runs [Infection](https://github.com/infection/infection)
- `make test` - Runs both test frameworks
- and running just `make` runs both the analisis and tests. 

You can use the `--keep going` flag to not stop on the first failure.
