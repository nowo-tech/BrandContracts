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

### Security

- Batch audit IP (before 1.0.0):
  - IP-L1: `Brand` refuses an `assets` value that is not a `BrandAsset` (a look-alike object skipped every asset rule).
  - IP-L2: text fields refuse blank or invisible-only values (Unicode whitespace, U+200B, NBSP, BOM…), bidi embedding
    / override / isolate controls (U+202A–U+202E, U+2066–U+2069) and line / paragraph separators. ZWJ and ZWNJ inside
    text stay allowed.
  - IP-I3: asset URLs refuse `,`, `(` and `)` (safe unquoted `srcset` candidates); the https host must be non-empty
    dot-separated labels and the port 1–65535.
  - IP-I4: a translatable eyebrow whose string form is not valid UTF-8 throws `InvalidArgumentException`, not
    `JsonException`.

### Changed

- IP-I5: text fields (`name`, `shortName`, a string `eyebrow`, `wordmark`) are trimmed of leading and trailing Unicode
  whitespace, so `" Beacon "` gives the wordmark `beacon`.

### Documentation

- USAGE.md: what the version covers for translatable eyebrows (class and string form; not domain or parameters; custom
  translatables must be `Stringable`) (IP-I1, IP-I2); new "API stability" section: new colour keys arrive optional in
  minors, `BrandAssetOrigin` may gain cases so gate on `isInlineSafe()` (IP-I6), `shades()` follows UiKit's emitter and
  may gain keys (IP-I7). SECURITY.md: text-field and URL rules updated.
