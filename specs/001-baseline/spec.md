# Feature Specification: Baseline (BrandContracts)

**Feature Branch**: `main` (baseline)
**Created**: 2026-11-08
**Status**: Baseline
**Source**: nowo-tech spec 220 (brand fully configurable from the bundles), section 2.3 and slice S0.

## Summary

`nowo-tech/brand-contracts` is the shared brand contract read by nowo-tech bundles (UiKit, Pwa, MailKit, LegalKit,
SeoKit) and host renderers (emails, PDFs). It is a framework-free package of interfaces and value objects.

## Scope of this baseline

The whole 1.0.0 API (slice S0): interfaces, value objects, the data URI helper and colour math. No I/O, no
framework wiring, no default provider.

## Requirements

- **FR-000**: `composer.json` declares `nowo-tech/brand-contracts`, type `library`, MIT, PHP `>=8.2 <8.6`, PSR-4
  namespace `Nowo\Brand\Contract\`, and no framework runtime dependency except `symfony/translation-contracts`.
- **FR-001**: `BrandAssetSlot` names the five fixed slots (`logo_light`, `logo_dark`, `mark`, `app_icon`, `favicon`)
  and the extra slots `extra.<id>` (`<id>` matches `[a-z][a-z0-9_]{0,63}`, no trailing newline); `isValid()`,
  `isExtra()`, `extra()` and `assertValid()` refuse everything else.

- **FR-002**: `BrandProviderInterface::getBrand(): Brand` is the only provider seam; the package ships no provider.
- **FR-003**: `Brand` refuses an empty or blank name, control characters or invalid UTF-8 in text fields, a short name
  over 12 grapheme clusters and an asset whose key differs from its slot. Defaults: short name = trimmed name cut at
  12 graphemes (trailing space trimmed), wordmark = `mb_strtolower(name)`, empty eyebrow = null.
- **FR-004**: `Brand::asset()` applies the chain of spec 220 section 4.2 (logo_dark → logo_light; mark → app_icon;
  app_icon → mark when square ±2 % and ≥ 512 px; favicon → mark → app_icon; extra itself) and refuses unknown slots.
- **FR-005**: `Brand::$version` is the first 16 hex characters of a SHA-256 over every field, independent of asset
  order.
- **FR-006**: `BrandColors` accepts `#rrggbb` only (lower-cased); `primaryContrast()` picks surface or text by WCAG
  ratio (surface on a tie); `mix()` accepts colour names or hex.
- **FR-007**: `BrandAsset` accepts root-relative or `https://host[:port]` URLs only (printable ASCII, no quotes, angle
  brackets, backticks, backslashes, whitespace or userinfo, ≤ 2048), `image/*` types, positive or null dimensions and
  64-hex sha256; uploads must be PNG, JPEG or WebP; `isInlineSafe()` is true only for `ConfigDefault` (Q-O4).
- **FR-008**: `BrandAssetDataUri` takes the type from PNG / JPEG / WebP magic bytes and accepts other bytes only from
  inline-safe assets; empty bytes are refused.
- **FR-009**: `ColorMath::mix()` equals CSS `color-mix(in srgb, a p%, b)` rounded half up; luminance uses the 0.03928
  threshold like UiKit's emitter; `shades()` returns UiKit's derived tokens in hex; `contrastWarnings()` returns the
  failed checks of section 3.3 (4.5 / 4.5 / 4.5 / 3).

## Success criteria

- CI green: tests on PHP 8.2 to 8.5, PHPStan max with no ignores, CS, Rector dry run, Igor, 100% coverage of `src/`.
