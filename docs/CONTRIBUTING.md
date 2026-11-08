# Contributing

## Table of contents

- [Code of Conduct](#code-of-conduct)
- [Development setup](#development-setup)
- [Code style](#code-style)
- [Pull requests](#pull-requests)
- [Cursor Agent](#cursor-agent)
- [Git hooks (REQ-GIT-001)](#git-hooks-req-git-001)

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](../CODE_OF_CONDUCT.md). By participating, you are expected to uphold it. Please report unacceptable behavior to **hectorfranco@nowo.tech**.

## Development setup

1. Clone the repository.
2. Start the dev container: `make up` then `make install`.
3. Run tests: `make test`, `make test-coverage-100`, `make cs-check`, `make phpstan`, `make validate-phpdoc`, `make igor`.
4. Pre-release: `make release-check`.

## Code style

- PHP-CS-Fixer (PSR-12 + Symfony rule sets): `make cs-fix` / `make cs-check`.
- PHPStan at level **max** with `ignoreErrors: []`, including the **`nowo-tech/phpstan-frankenphp`** rulesets
  (REQ-CS-005 / REQ-CS-006; `require-dev` only).
- Rector (dry run in CI), Igor worker-state audit.
- PHPDoc on every class and public method, and Markdown docs, in **English**.
- Strict types in all PHP files.

## Pull requests

- Target `main`.
- Ensure `make release-check` passes.
- Keep the changelog, docs and `specs/001-baseline/` updated when behaviour changes.
- The public API is a long-lived contract: additions only within 1.x.

## Cursor Agent

Contributors using Cursor Agent on this repository should enable these user-level settings (REQ-IDE-006):

1. **Submit with Ctrl + Enter** (macOS: **⌘ + Enter**) — Cursor Settings → Agents / Chat → Submit with Ctrl + Enter. Enter inserts a newline; Ctrl/⌘+Enter submits the prompt.
2. **Queue follow-ups while the agent is running** — in your user `settings.json` (Linux `~/.config/Cursor/User/settings.json`, Windows `%APPDATA%\Cursor\User\settings.json`, macOS `~/Library/Application Support/Cursor/User/settings.json`):

```json
{
  "cursor.composer.queueMessageDefaultBehavior": "queue"
}
```

Do not use `"steer"` as the default: it interrupts the running agent turn as soon as the follow-up is sent.

## Git hooks (REQ-GIT-001)

Do **not** add `Co-authored-by: Cursor` or `cursoragent@cursor.com` trailers, nor `Co-Authored-By: Claude …` / `noreply@anthropic.com` ones, to commit messages.

```bash
make setup-hooks
make check-no-cursor-coauthor
```

`make setup-hooks` sets `core.hooksPath` to `.githooks` (the `commit-msg` hook strips such trailers). Run it once per clone before your first commit.
