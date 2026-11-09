# Usage

Namespace `Nowo\Brand\Contract`. Design source: nowo-tech spec 220, section 2.3.

## Table of contents

- [Overview](#overview)
- [Providing a brand](#providing-a-brand)
- [Consuming the brand](#consuming-the-brand)
  - [Symfony wiring](#symfony-wiring)
  - [Name, short name, eyebrow, wordmark](#name-short-name-eyebrow-wordmark)
  - [Colours](#colours)
  - [Assets and the fallback chain](#assets-and-the-fallback-chain)
  - [Embedding assets in emails and PDFs](#embedding-assets-in-emails-and-pdfs)
  - [Caching with the version](#caching-with-the-version)
- [Using it from a DDD / hexagonal application](#using-it-from-a-ddd--hexagonal-application)
- [API stability](#api-stability)
- [API reference](#api-reference)

## Overview

| Type | Role |
| --- | --- |
| `BrandProviderInterface` | `getBrand(): Brand`. Implemented by a provider (InstanceSettings, a host class). |
| `Brand` | Name, short name, eyebrow, wordmark, light / dark colours, assets, `isDefault`, `version`. |
| `BrandColors` | Seven `#rrggbb` colours of one mode. |
| `BrandAsset` | One image: slot, URL, media type, size, sha256, origin. |
| `BrandAssetOrigin` | `Uploaded` or `ConfigDefault`. |
| `BrandAssetSlot` | Slot names: `logo_light`, `logo_dark`, `mark`, `app_icon`, `favicon`, `extra.<id>`. |
| `BrandAssetReaderInterface` | Bytes of an asset or of a variant, for CID attachments and data URIs. |
| `BrandAssetReadException` | Thrown by readers. |
| `BrandAssetDataUri` | Data URI / media type from bytes, with the inlining rule. |
| `ColorMath` | Contrast, mixes, derived shades, contrast warnings. |

Every value object validates on construction and throws `InvalidArgumentException` on a bad value, so a consumer
never sees an empty name, a non-hex colour, an unknown slot, an asset that is not a `BrandAsset` or an unsafe URL.

## Providing a brand

InstanceSettingsBundle (1.5+) provides the brand from its `brand` and `appearance` sections. Without it, a host class
is enough:

```php
use Nowo\Brand\Contract\Brand;
use Nowo\Brand\Contract\BrandAsset;
use Nowo\Brand\Contract\BrandAssetOrigin;
use Nowo\Brand\Contract\BrandColors;
use Nowo\Brand\Contract\BrandProviderInterface;

final class AppBrandProvider implements BrandProviderInterface
{
    public function getBrand(): Brand
    {
        return new Brand(
            name: 'Beacon',
            light: new BrandColors('#1f6f54', '#134736', '#b42318', '#b54708', '#f3f6f4', '#0f1c18', '#ffffff'),
            dark: new BrandColors('#4aad7f', '#6bc49a', '#f97066', '#fdb022', '#0c1210', '#e6eee9', '#151c19'),
            assets: [
                'mark' => new BrandAsset('mark', '/brand/beacon-mark.svg', 'image/svg+xml', null, null,
                    hash_file('sha256', __DIR__.'/../public/brand/beacon-mark.svg'), BrandAssetOrigin::ConfigDefault),
            ],
            isDefault: true,
        );
    }
}
```

Rules for providers:

- Return a brand with your defaults and `isDefault: true` while nothing is stored; never an empty name.
- Put in `assets` only the slots that resolve (an upload or a config default), keyed by slot; every value must be a
  `BrandAsset` whose `slot` equals its key. The cross-slot fallback chain is applied by `Brand::asset()`, not by the
  provider.
- An uploaded asset must be PNG, JPEG or WebP (`BrandAsset` refuses anything else with origin `Uploaded`). Config
  default assets (committed files) may be SVG or ICO.
- Memoise per request if building the brand costs a query, and reset the memo between requests in a worker.

## Consuming the brand

### Symfony wiring

Inject the provider as optional and keep your own configuration as the fallback:

```php
public function __construct(
    #[Autowire(service: BrandProviderInterface::class, onInvalid: ContainerInterface::NULL_ON_INVALID_REFERENCE)]
    private readonly ?BrandProviderInterface $brand = null,
    private readonly string $configuredSiteName = 'My App',
) {}

private function siteName(): string
{
    return $this->brand?->getBrand()->name ?? $this->configuredSiteName;
}
```

In a YAML service definition: `'@?Nowo\Brand\Contract\BrandProviderInterface'`. Error pages and other code that must
work with a broken database should wrap `getBrand()` in a `try` and fall back to config.

### Name, short name, eyebrow, wordmark

- `name`: trimmed; never blank or invisible-only, no control characters, bidi controls or line separators (see
  [SECURITY.md](SECURITY.md#text-fields)). `" Beacon "` is stored as `Beacon`.
- `shortName`: at most 12 grapheme clusters; defaults to the name cut at 12 (`International Widgets` → `Internationa`).
  The same rules and trimming apply to every text field (`shortName`, a string `eyebrow`, `wordmark`).
- `eyebrow`: `null`, a string, or a `TranslatableInterface` (an untouched default that follows the viewer's locale).
  `eyebrowText($translator, $locale)` gives the text either way.
- `wordmark`: defaults to the lower-cased (trimmed) name.

### Colours

```php
$colors = $brand->colors(dark: false);       // BrandColors, same as $brand->light
$colors->primary;                            // '#1f6f54' (always lower-case #rrggbb)
$colors->primaryContrast();                  // '#ffffff': surface or text, whichever contrasts more
$colors->mix('primary', 10, 'paper');        // '#dee9e4' = color-mix(in srgb, primary 10%, paper)

ColorMath::shades($colors);                  // ['accent-soft' => '#dee9e4', 'border' => '#d3d7d5', ...]
ColorMath::contrastWarnings($colors);        // [] or ['text_on_paper', ...] (advisory, never blocking)
ColorMath::contrastRatio('#0f1c18', '#f3f6f4');
```

`ColorMath::shades()` returns the hex equivalents of UiKit's derived tokens (`primary-hover`, `primary-contrast`,
`border`, `border-strong`, `accent-soft`, `surface-muted`, `paper-accent`, `paper-ink`, `muted`, `brand-mark`) for
renderers without `color-mix()`. Pass the border percentage of the border strength in use (default 14, UiKit
"medium" light). `shades()` follows UiKit's `AppearanceCssEmitter` (2.38 today): when UiKit derives a new token, a
minor release may add a key, so read the keys you need instead of relying on the exact key set. `border-strong`
currently equals `border`, as in UiKit.

Mapping to UiKit's palette: primary = accent, primaryDeep = accent_deep, warning = warn, text = ink; danger, paper and
surface keep their names.

### Assets and the fallback chain

```php
$favicon = $brand->asset(BrandAssetSlot::FAVICON);   // favicon → mark → app_icon → null
if (null === $favicon) {
    // print your own <link rel="icon">, or nothing
}
```

| Slot | Chain |
| --- | --- |
| `logo_light` | itself (then the consumer renders mark + name text) |
| `logo_dark` | itself → `logo_light` |
| `mark` | itself → `app_icon` (then the name initial) |
| `app_icon` | itself → `mark` if square (±2 %) and at least 512 px (then the consumer's own icons) |
| `favicon` | itself → `mark` → `app_icon` |
| `extra.<id>` | itself |

The returned asset keeps its own `slot`. `url` is safe for an `<img src>` / `<link href>` attribute (still escape it
as usual). Never put it in a CSS `url()` custom property.

### Embedding assets in emails and PDFs

Use the provider's `BrandAssetReaderInterface` to get bytes, and follow the inlining rule (spec 220, section 7.5):

```php
$mark = $brand->asset(BrandAssetSlot::MARK);
if (null !== $mark && $mark->isInlineSafe() && $mark->isSvg()) {
    $svg = $reader->read($mark);                          // trusted committed SVG: may be inlined
} elseif (null !== $mark) {
    $src = BrandAssetDataUri::fromBytes($mark, $reader->read($mark));   // <img src="data:image/png;base64,...">
}
```

- `isInlineSafe()` is true only for `ConfigDefault` assets. Uploaded bytes never go into markup.
- `BrandAssetDataUri::fromBytes()` / `mimeTypeOf()` take the type from the bytes (PNG, JPEG, WebP magic); other bytes
  are accepted only from an inline-safe asset. Use `mimeTypeOf()` for a CID attachment's content type.
- Variants (`read($asset, 192)`) are generated square PNGs; which sizes exist is the provider's business
  (InstanceSettings: 180, 192, 512 for `app_icon`; 32, 16 for `favicon`). A missing variant throws
  `BrandAssetReadException`.

### Caching with the version

`Brand::$version` is a 16-character hash of every field (asset order does not matter). Use it in ETags and cache
keys; add the locale when the eyebrow is translatable (its text depends on the locale, the version does not).

What the version covers for a translatable eyebrow:

- its class and its string form (`(string) $eyebrow`), which is the message key for Symfony's `TranslatableMessage`;
- **not** its translation domain or parameters: the same key in another domain, or with other parameters, gives the
  same version;
- for a translatable that is not `Stringable`, only its class: two such eyebrows of the same class always give the same
  version. Custom translatables must be `Stringable` (returning at least the key) to be versioned.

Change the key, not only the domain or parameters, when the default eyebrow changes and caches must follow. A string
form that is not valid UTF-8 is refused with `InvalidArgumentException`. The version is a cache key, not a security
token, and the hashing scheme may change in a minor release (never rely on a literal value).

## Using it from a DDD / hexagonal application

**Layer:** contract. Brand Contracts holds two ports (`BrandProviderInterface`, `BrandAssetReaderInterface`) and
immutable, self-validating value objects (`Brand`, `BrandColors`, `BrandAsset`, `BrandAssetOrigin`,
`BrandAssetSlot`). It has no Doctrine, no framework code and no default implementation. It depends only on
`ext-mbstring` and `symfony/translation-contracts`.

**How to use it**

- Treat `BrandProviderInterface` as an outbound port of your presentation and notification layers. Implement it in
  infrastructure, for example from InstanceSettingsBundle or a small class reading your own `Branding` aggregate,
  and translate your model into `Brand` there. Your domain never needs to import this package.
- Implement `BrandAssetReaderInterface` where your asset bytes live (filesystem, object storage, database). Throw
  `BrandAssetReadException` without paths or content in the message.
- Use `Brand::$version` as the cache key or ETag for anything derived from the brand.

```php
final class BrandingBrandProvider implements BrandProviderInterface
{
    public function __construct(private BrandingRepository $brandings, private CurrentProject $project) {}

    public function getBrand(): Brand
    {
        $b = $this->brandings->forProject($this->project->id()); // your domain
        return new Brand(
            name: $b->name(),
            light: BrandColors::fromArray($b->lightPalette()),
            dark: BrandColors::fromArray($b->darkPalette()),
            isDefault: $b->isDefault(),
        );
    }
}
```

**Known limitations**

- `getBrand()` takes no scope. In a multi-project host the provider resolves the current project from ambient
  state, so set that context in Messenger handlers and CLI jobs before rendering.
- No "brand changed" event contract yet. Compare `Brand::$version` to detect changes.

## API stability

1.x follows semantic versioning. What may change in a minor release:

- **Colours.** `BrandColors` may gain colour keys (for example `success`, `info`). They arrive as optional trailing
  constructor parameters with a default, and `fromArray()` will accept them as optional keys; the seven current keys
  stay required. Use named arguments or `fromArray()` when you build colours, and never assume `KEYS` / `toArray()` has
  exactly seven entries.
- **Asset origins.** `BrandAssetOrigin` may gain cases (for example sanitised SVG uploads). Decide whether bytes may be
  inlined with `BrandAsset::isInlineSafe()`, never with an exhaustive `match` over the enum.
- **Shades.** `ColorMath::shades()` follows UiKit's emitter and may gain keys.
- **Version.** The algorithm behind `Brand::$version` may change; only its length (`VERSION_LENGTH`) and its
  "changes when a field changes" property are stable.

A stricter validation rule is a behaviour change for providers: after 1.0.0 it ships in a minor with a CHANGELOG and
UPGRADING note.

- **Asset URLs** (audit IP-I8): literal `,`, `(` and `)` are refused (unsafe in `srcset` and unquoted `url()`). A CDN URL with commas (e.g. `w_200,h_100`) or parentheses must percent-encode them (`%2C`, `%28`, `%29`); IPv6 literals and a trailing-dot FQDN are not accepted either.

## API reference

Every public class and method carries a PHPDoc block in `src/`. Public constants: `Brand::SHORT_NAME_MAX`,
`Brand::VERSION_LENGTH`, `BrandColors::KEYS`, `BrandAsset::UPLOAD_MIME_TYPES`, `BrandAsset::SVG_MIME_TYPE`,
`BrandAsset::MAX_URL_LENGTH`, `BrandAsset::SQUARE_TOLERANCE`, `BrandAssetSlot::*`, `ColorMath::AA_TEXT`,
`ColorMath::AA_NON_TEXT`, `ColorMath::DEFAULT_BORDER_PERCENT`.
