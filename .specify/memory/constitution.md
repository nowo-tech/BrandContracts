# BrandContracts Constitution

## Core Principles

### I. Documented integrator contract
Product behavior lives in `specs/001-baseline/spec.md`, `docs/SPEC-DRIVEN-DEVELOPMENT.md`, and integrator docs (`USAGE.md`, `CONFIGURATION.md`). The design source is nowo-tech spec 220 (brand fully configurable from the bundles).

### II. Spec-first, test-proven
PHPUnit and PHPStan are the mechanical proof. Behavioral changes require tests. Coverage of `src/` stays at 100%.

### III. 100% code inventory traceability
Every production file under `src/` must appear in `specs/001-baseline/code-inventory.md`. New files require spec updates in the same PR.

### IV. Cursor + Spec Kit
GitHub Spec Kit is initialized with **Cursor Agent** (`cursor-agent`). Skills live in `.cursor/skills/speckit-*`.

### V. Tiny and stable
Interfaces and value objects only, PHP `>=8.2 <8.6`, no framework runtime dependency except `symfony/translation-contracts`. The public API stays on 1.x for years; additions only.

### VI. Fail closed
Value objects validate in their constructor and refuse what they cannot prove safe (non-hex colours, unknown slots, unsafe URLs). Uploaded assets are never inline-safe.

### VII. Worker-safe
No mutable static state, no I/O in value objects; readers are stateless.

## Governance
Amendments update this file, baseline spec when principles affect behavior, and `CHANGELOG.md` when consumer-visible.

**Version**: 1.0.0 | **Ratified**: 2026-11-08 | **Last Amended**: 2026-11-08
