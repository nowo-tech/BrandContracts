# PHP-FIG PSR evaluation (REQ-CS-007)

Package: `nowo-tech/brand-contracts` (`library`)

This document records which [PHP-FIG PSRs](https://www.php-fig.org/psr/) apply to this package.
Only contracts that add clear interoperability or maintainability value are **Adopted**.

## Table of contents

- [Baseline (always)](#baseline-always)
- [Interface / contract PSRs](#interface--contract-psrs)
- [Summary](#summary)

## Baseline (always)

| PSR | Decision | How |
| --- | -------- | --- |
| PSR-12 (coding style) | **Adopted** | `@PSR12` in `.php-cs-fixer.dist.php` (Nowo REQ-CS-001). |
| PSR-4 (autoloading) | **Adopted** | `composer.json` `autoload` / `autoload-dev` PSR-4 map (`Nowo\Brand\Contract\`). |

## Interface / contract PSRs

| PSR | Decision | Notes |
| --- | -------- | ----- |
| PSR-3 Logger | **N/A** | Value objects and interfaces only; nothing to log. |
| PSR-6 / PSR-16 Cache | **N/A** | No cache; `Brand::$version` gives consumers a cache key. |
| PSR-7 / PSR-17 HTTP messages | **N/A** | No HTTP. Asset bytes are read by the provider's `BrandAssetReaderInterface`. |
| PSR-11 Container | **N/A** | No services; wiring belongs to the bundles. |
| PSR-14 Event dispatcher | **N/A** | No events. |
| PSR-18 HTTP client | **N/A** | No outbound request (no SSRF surface). |
| PSR-20 Clock | **N/A** | No time-dependent behaviour. |

## Summary

- **Adopted beyond baseline:** none. The package is a contract; it must stay dependency-light.
- **Rule:** do not add `psr/*` Composer dependencies without a matching public type-hint.

---

_REQ-CS-007 evaluation date: 2026-11-08._
