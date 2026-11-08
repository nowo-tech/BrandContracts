# Spec-driven development

In this repository, **spec-driven development** has three layers that stay in sync:

1. **GitHub Spec Kit baseline** — [`specs/001-baseline/`](../specs/001-baseline/) ([`spec.md`](../specs/001-baseline/spec.md), [`code-inventory.md`](../specs/001-baseline/code-inventory.md)), initialized with [GitHub Spec Kit](https://github.com/github/spec-kit) (`.specify/`, **Cursor Agent** skills in `.cursor/skills/speckit-*`). The inventory maps **100%** of production code in `src/`. **How to install, initialize, and use Spec Kit:** [`SPEC-KIT.md`](SPEC-KIT.md).
2. **Product behavior** — what **BrandContracts** guarantees to the packages and applications that use it (see [`USAGE.md`](USAGE.md), [`CONFIGURATION.md`](CONFIGURATION.md), [`INSTALLATION.md`](INSTALLATION.md)). **PHPUnit** and **PHPStan** enforce contracts in CI where applicable.
3. **Traceability anchors** — stable **`REQ-*`** identifiers in the Makefile and CI so changes to scripts and CI stay discoverable from issues and PRs.

There is no separate executable spec language (for example Gherkin); Spec Kit specs, tests, and static analysis are the mechanical proof alongside this document.

---

## Table of contents

- [User stories](#user-stories)
- [Package functional scope](#package-functional-scope)
- [Validating the functional spec](#validating-the-functional-spec)
- [Requirement identifiers (`REQ-*`)](#requirement-identifiers-req-)
- [Suggested workflow for contributors](#suggested-workflow-for-contributors)
- [Relationship to Engram / external checklists](#relationship-to-engram--external-checklists)
- [GitHub Spec Kit (summary)](#github-spec-kit-summary)
- [See also](#see-also)

## User stories

The sections below state **behavior**; this subsection states **intent** in backlog-friendly form.

| ID | Story |
| --- | --- |
| US-01 | **As a** bundle author, **I want** one small contract for the brand **so that** my bundle reads the host's name, colours and logos without depending on another bundle's configuration. |
| US-02 | **As a** host integrator, **I want** to provide the brand with one small class or with InstanceSettings **so that** every bundle shows the same identity. |
| US-03 | **As an** email or PDF renderer, **I want** hex colours and precomputed shades **so that** I need no CSS `color-mix()`. |
| US-04 | **As a** security reviewer, **I want** the contract to say which asset bytes may be inlined **so that** an uploaded file never reaches inline markup. |
| US-05 | **As a** maintainer, **I want** behavior changes covered by automated tests **so that** regressions are caught in CI. |

**Out of scope for these stories:** guarantees outside the stated public API and outside dependency limits.

---

## Package functional scope

**Goal:** the shared brand contract of nowo-tech spec 220 (section 2.3): interfaces and value objects only.

**In scope**

- `BrandProviderInterface`, `Brand`, `BrandColors`, `BrandAsset`, `BrandAssetOrigin`, `BrandAssetSlot`,
  `BrandAssetReaderInterface`, `BrandAssetReadException`, `BrandAssetDataUri`, `ColorMath` (1.0.0).
- Validation rules of the value objects and the inlining rule documented in [`SECURITY.md`](SECURITY.md).

**Explicit non-goals**

- Storage, upload, image processing, HTTP serving: the provider's job (InstanceSettings).
- A default provider: consumers keep their own configuration as the fallback.
- Symfony wiring, Twig functions, CSS emission.

---

## Validating the functional spec

- Run **`composer qa`** and/or **`make qa`** / **`make release-check`** as documented in [`CONTRIBUTING.md`](CONTRIBUTING.md) (Docker-based flows may apply).
- Run **PHPUnit** and **PHPStan** in CI and locally for code changes.
- New or changed behavior should add or adjust **tests** under `tests/` (or the repo’s documented test layout) rather than relying on prose alone.

---

## Requirement identifiers (`REQ-*`)

| ID | Where | What it marks |
| --- | --- | --- |
| REQ-MAKE-002 | `Makefile` `release-check` | Sequential pre-release chain (co-author / open PRs, composer-sync, CS, Rector, PHPStan, PHPDoc, Igor, coverage + 100% gate). |
| REQ-TEST-003 / REQ-TEST-006 | `Makefile` `coverage-check`, `test-coverage-100`; `composer test-coverage-100` | 100% statements and elements of `src/`, no exclusions. |
| REQ-CS-001 | `Makefile` `validate-phpdoc` | PHPDoc on every class and public method of `src/`. |
| REQ-GIT-001 | CI job `git-hygiene`, `.githooks/commit-msg` | No AI co-author trailers. |

When you change scripted behavior, **update the existing `REQ-*` comment** if the ID still matches the rule, or **add a new `REQ-*`** and document it here and in the PR description.

---

## Suggested workflow for contributors

1. **Clarify behavior** in an issue or draft PR: acceptance criteria for the **product** and, if relevant, the **Makefile / CI** (`REQ-*`).
2. **Implement** with tests and static analysis.
3. **Anchor scripts** when dev UX changes: add or adjust `REQ-*` comments and this table.
4. **Ship integrator docs** when behavior or configuration changes: [`USAGE.md`](USAGE.md), [`CONFIGURATION.md`](CONFIGURATION.md), [`CHANGELOG.md`](CHANGELOG.md), and [`UPGRADING.md`](UPGRADING.md) when consumers must change code or config.
5. **Keep Spec Kit artifacts in sync** when production code under `src/` changes:
   - Update [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md) and [`code-inventory.md`](../specs/001-baseline/code-inventory.md).
   - Follow the maintainer checklist in [`SPEC-KIT.md`](SPEC-KIT.md).
   - For **new features**, use Cursor Agent skills (`/speckit-specify`, `/speckit-plan`, `/speckit-tasks`) as documented in SPEC-KIT.


---

## Relationship to Engram / external checklists

[`ENGRAM.md`](ENGRAM.md) covers Nowo-wide documentation checklist items. This document ties together **what the package does**, **how we verify it**, and **local `REQ-*` habits**. Both coexist: Engram for org-level compliance, this file for product + traceability expectations.

---

## GitHub Spec Kit (summary)

This repository uses [GitHub Spec Kit](https://github.com/github/spec-kit) with **Cursor Agent** (`cursor-agent` integration).

| Artifact | Path |
| --- | --- |
| **Operator manual** (install, init, usage) | [`SPEC-KIT.md`](SPEC-KIT.md) |
| Baseline spec | [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md) |
| Code inventory (100%) | [`specs/001-baseline/code-inventory.md`](../specs/001-baseline/code-inventory.md) |
| Constitution | [`.specify/memory/constitution.md`](../.specify/memory/constitution.md) |
| Cursor Agent skills | [`.cursor/skills/`](../.cursor/skills/) (`speckit-*`) |

**Quick start (maintainers):**

```bash
# Install Specify CLI (once per machine) — see SPEC-KIT.md
specify init --here --force --integration cursor-agent --script sh
specify integration list   # Cursor → installed (default)
```

In Cursor Agent, start a new feature with `/speckit-specify <description>`. For day-to-day tooling details, skills reference, folder layout, and troubleshooting, read **[`SPEC-KIT.md`](SPEC-KIT.md)**.

---

## See also

- [`SPEC-KIT.md`](SPEC-KIT.md) — GitHub Spec Kit manual (install, structure, usage)
- [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md)
- [`USAGE.md`](USAGE.md)
- [`CONFIGURATION.md`](CONFIGURATION.md)
- [`CONTRIBUTING.md`](CONTRIBUTING.md)
- [`RELEASE.md`](RELEASE.md)
