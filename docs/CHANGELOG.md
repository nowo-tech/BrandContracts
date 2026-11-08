# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Repository scaffold: package `nowo-tech/brand-contracts` (`Nowo\Brand\Contract\`), Docker / Makefile / CI,
  release workflows, Spec Kit baseline and documentation.
- `BrandAssetSlot`: the five fixed slot names and `extra.<id>` slots with validation helpers.
- Brand contract of spec 220 (slice S0, target 1.0.0):
  - `BrandProviderInterface::getBrand(): Brand`.
  - `Brand` (`final readonly`): name (never empty, no control characters), short name (at most 12 grapheme clusters,
    default the name cut at 12), eyebrow (string or `TranslatableInterface`, `eyebrowText()`), wordmark (default the
    lower-cased name), light / dark `BrandColors`, assets keyed by slot, `isDefault`, `version` (16-hex hash), and
    `asset($slot)` with the fallback chain of section 4.2.
  - `BrandColors` (`final readonly`): seven `#rrggbb` colours, lower-cased; `fromArray()`, `toArray()`, `get()`,
    `primaryContrast()`, `mix()`.
  - `BrandAsset` (`final readonly`) and `BrandAssetOrigin` (`Uploaded`, `ConfigDefault`): safe URLs only (root-relative
    or `https://`), uploads raster only (PNG, JPEG, WebP), `isInlineSafe()` true only for config defaults (Q-O4),
    `isSvg()`, `isSquare()`, `minSide()`, `isSafeUrl()`.
  - `BrandAssetReaderInterface`, `BrandAssetReadException` and `BrandAssetDataUri` (data URI / media type from sniffed
    bytes; non-raster bytes only from inline-safe assets).
  - `ColorMath`: `isHex()`, `normalize()`, `rgb()`, `rgbChannels()`, `relativeLuminance()`, `contrastRatio()`,
    `contrastingOf()`, `mix()` (= CSS `color-mix(in srgb, …)`), `shades()` (UiKit derived tokens in hex),
    `contrastWarnings()` (advisory checks of section 3.3).
