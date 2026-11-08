# Security

## Table of contents

- [Scope](#scope)
- [Attack surface](#attack-surface)
- [Dependencies and updates](#dependencies-and-updates)
- [Audit status](#audit-status)

## Scope

`nowo-tech/brand-contracts` is a contract package (interfaces and value objects). It performs no I/O, opens no
endpoint, renders no markup and keeps no secret. The threat model and the asset inlining rule are documented with the
1.0.0 API.

## Attack surface

The repository scaffold ships only `BrandAssetSlot`, which validates slot names (closed list plus a strict
`extra.<id>` pattern anchored with `\z`, so no path separator, dot or newline reaches a provider's lookup).

## Dependencies and updates

- Runtime: `symfony/translation-contracts` only. QA tools stay in `require-dev`.
- `composer audit` runs in CI on every push and pull request; Dependabot opens weekly Composer and GitHub Actions
  updates.

## Audit status

REQ-SEC-004: batch audit before the 1.0.0 tag (the package has no I/O).
