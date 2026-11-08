# Release process

## Table of contents

- [Before the first tag (1.0.0)](#before-the-first-tag-100)
- [Standard workflow](#standard-workflow)

## Before the first tag (1.0.0)

1. The package is registered on Packagist (the owner registers it once a valid `composer.json` is on `main`).
2. Security audit recorded (REQ-SEC-004; batch audit, the package does no I/O) — see [SECURITY.md](SECURITY.md).
3. [CHANGELOG.md](CHANGELOG.md) — move `[Unreleased]` to `## [1.0.0] - YYYY-MM-DD`.
4. [UPGRADING.md](UPGRADING.md) — "Adopting 1.0.0" section current.
5. `make release-check` green (check-no-cursor-coauthor, check-open-prs, composer-sync, cs-fix, cs-check, rector-dry,
   phpstan, validate-phpdoc, igor, test-coverage, coverage-check).
6. CI green on the exact release commit.
7. Annotated tag `git tag -a v1.0.0 -m "Release v1.0.0"`; run `make check-no-cursor-coauthor` again before pushing
   (REQ-GIT-001).
8. Push the tag; `release.yml` creates the GitHub Release from the tag message and the changelog entry.
9. Confirm Packagist picked up the tag.

## Standard workflow

1. Move `[Unreleased]` entries in [CHANGELOG.md](CHANGELOG.md) to a new `## [x.y.z] - YYYY-MM-DD` section.
2. Update [UPGRADING.md](UPGRADING.md) when consumers must act.
3. Update [USAGE.md](USAGE.md) and [SECURITY.md](SECURITY.md) when behaviour changes.
4. Run `make release-check`.
5. Commit, tag (`git tag -a vx.y.z -m "Release vx.y.z"`), push branch and tag.
6. Verify the GitHub Release and Packagist.

This is a contract package: a 2.0.0 drags every consumer's constraint, so breaking changes need an owner decision.
