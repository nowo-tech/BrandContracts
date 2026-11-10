# Brand Contracts

[![CI](https://github.com/nowo-tech/BrandContracts/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/BrandContracts/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/brand-contracts.svg?style=flat)](https://packagist.org/packages/nowo-tech/brand-contracts) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/brand-contracts.svg)](https://packagist.org/packages/nowo-tech/brand-contracts) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/BrandContracts.svg?style=social&label=Star)](https://github.com/nowo-tech/BrandContracts) [![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen)](#tests-and-coverage)

> ⭐ **Found this useful?** [Install from Packagist](https://packagist.org/packages/nowo-tech/brand-contracts) · Give it a **star** on [GitHub](https://github.com/nowo-tech/BrandContracts) so more developers can find it.

**Brand Contracts** — the shared brand contract for PHP apps and nowo-tech bundles: the brand name, colours and logo
assets a host provides, read the same way by UI shells, PWA manifests, emails, PDFs, legal texts and SEO tags. It is a
tiny, framework-free package (interfaces and value objects, plus colour math for renderers that cannot use CSS
`color-mix()`). A provider implements one interface; every consumer works without a provider and keeps its own
configuration as the fallback. PHP 8.2+.

![FrankenPHP Friendly Worker Mode](docs/images/frankenphp-friendly.png)

This bundle is **FrankenPHP worker mode friendly**.

Value objects are immutable and the package keeps no static state, so a worker that never resets the kernel is safe.
See [docs/FRANKENPHP-WORKER-AUDIT.md](docs/FRANKENPHP-WORKER-AUDIT.md).

## Features

- **`BrandProviderInterface`**: one method, `getBrand(): Brand`. InstanceSettings implements it; a host can too, in a
  few lines. No default provider: consumers keep their config as the fallback. Multi-project hosts can implement
  `ScopedBrandProviderInterface::getBrandFor(BrandScope)` for Messenger and CLI code (1.1).
- **`Brand`**: name (never empty), short name (at most 12 graphemes), eyebrow (string or translatable), wordmark,
  light and dark `BrandColors`, resolved assets, `isDefault` and a `version` hash for ETags and cache keys.
- **Logo fallback chain**: `Brand::asset('favicon')` tries favicon, mark, app icon (spec 220, section 4.2).
- **`BrandColors`**: seven `#rrggbb` colours per mode, validated and lower-cased; `primaryContrast()`, `mix()`.
- **`ColorMath`**: WCAG luminance and contrast, sRGB mixes equal to CSS `color-mix(in srgb, …)`, UiKit's derived
  shades in hex, the advisory contrast checks.
- **Safe assets**: `BrandAsset` accepts only root-relative or `https://` URLs; uploads are raster only and never
  inline-safe; `BrandAssetDataUri` builds data URIs from sniffed bytes for emails and PDFs.

## Installation

```bash
composer require nowo-tech/brand-contracts
```

See [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Quick start

```php
use Nowo\Brand\Contract\BrandProviderInterface;

final class InvoiceMailer
{
    public function __construct(private readonly ?BrandProviderInterface $brand = null) {}

    public function senderName(): string
    {
        return $this->brand?->getBrand()->name ?? 'My App';   // own config stays the fallback
    }
}
```

## Requirements

- PHP 8.2+ (`>=8.2 <8.6`) with `mbstring`
- `symfony/translation-contracts` ^3.4 (the only runtime dependency; no framework needed)

## Configuration

None: the package has no configuration. See [docs/CONFIGURATION.md](docs/CONFIGURATION.md).

## Usage

See [docs/USAGE.md](docs/USAGE.md). What may change in a 1.x minor (new optional colour keys, new `BrandAssetOrigin`
cases, new `shades()` keys) is listed under [API stability](docs/USAGE.md#api-stability).

## Development

```bash
make up
make install
make test
make cs-check
make phpstan
make igor
make test-coverage-100
make release-check
```

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Engram](docs/ENGRAM.md)
- [Spec-driven development](docs/SPEC-DRIVEN-DEVELOPMENT.md)
- [GitHub Spec Kit](docs/SPEC-KIT.md)

### Additional documentation

- [PSR evaluation (REQ-CS-007)](docs/PSR.md)
- [FrankenPHP worker audit (kernel not reset)](docs/FRANKENPHP-WORKER-AUDIT.md)
- [GitHub Actions CI requirements](docs/GITHUB_CI.md)

## Tests and coverage

- Tests: PHPUnit (PHP), unit tests only (no I/O, no framework): validation tables, fallback chains, colour math
  checked against UiKit's emitter values for the Beacon palette
- PHP: 100% (gate enforced in CI by `composer test-coverage-100`, no exclusions)
- TS/JS: N/A
- Python: N/A

## License and author

MIT · [Nowo.tech](https://nowo.tech) · [Héctor Franco Aceituno](https://github.com/HecFranco)
