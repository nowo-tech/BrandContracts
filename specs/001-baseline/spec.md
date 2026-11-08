# Feature Specification: Baseline (BrandContracts)

**Feature Branch**: `main` (baseline)
**Created**: 2026-11-08
**Status**: Baseline
**Source**: nowo-tech spec 220 (brand fully configurable from the bundles), section 2.3 and slice S0.

## Summary

`nowo-tech/brand-contracts` is the shared brand contract read by nowo-tech bundles (UiKit, Pwa, MailKit, LegalKit,
SeoKit) and host renderers (emails, PDFs). It is a framework-free package of interfaces and value objects.

## Scope of this baseline

Repository scaffold: package manifest, QA tooling, CI and documentation, plus the slot names. The rest of the API is
specified and added by slice S0 (1.0.0) in the same directory.

## Requirements

- **FR-000**: `composer.json` declares `nowo-tech/brand-contracts`, type `library`, MIT, PHP `>=8.2 <8.6`, PSR-4
  namespace `Nowo\Brand\Contract\`, and no framework runtime dependency except `symfony/translation-contracts`.
- **FR-001**: `BrandAssetSlot` names the five fixed slots (`logo_light`, `logo_dark`, `mark`, `app_icon`, `favicon`)
  and the extra slots `extra.<id>` (`<id>` matches `[a-z][a-z0-9_]{0,63}`, no trailing newline); `isValid()`,
  `isExtra()`, `extra()` and `assertValid()` refuse everything else.

## Success criteria

- CI green: tests on PHP 8.2 to 8.5, PHPStan max with no ignores, CS, Rector dry run, Igor, 100% coverage of `src/`.
