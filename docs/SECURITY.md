# Security

## Table of contents

- [Scope](#scope)
- [Attack surface](#attack-surface)
- [Threat model](#threat-model)
- [Mitigations](#mitigations)
  - [Inlining rule and SVG](#inlining-rule-and-svg)
  - [URLs](#urls)
  - [Text fields](#text-fields)
  - [Colours](#colours)
  - [Slots](#slots)
- [Secrets and cryptography](#secrets-and-cryptography)
- [Logging](#logging)
- [Dependencies and updates](#dependencies-and-updates)
- [Provider and consumer checklist](#provider-and-consumer-checklist)
- [Audit status](#audit-status)

## Scope

`nowo-tech/brand-contracts` is a contract package: interfaces, value objects and pure helpers. It performs no I/O,
opens no endpoint, renders no markup, keeps no state and holds no secret. Storage, upload validation (sniffing,
re-encoding, size limits) and HTTP serving of brand images belong to the provider (InstanceSettingsBundle, spec 220
section 5). This package fixes the rules every provider and consumer share.

## Attack surface

- Values a provider passes to the constructors of `Brand`, `BrandColors` and `BrandAsset`. They may come from an
  administrator (stored settings) or from host configuration.
- Bytes a consumer passes to `BrandAssetDataUri` (read through a provider's `BrandAssetReaderInterface`).

## Threat model

| Threat | Where it would land |
| --- | --- |
| Stored XSS / script in an uploaded SVG | HTML emails, PDF engines, pages that inline a logo |
| SSRF / local file reads from SVG (`<image href>`, `<use>`, entities) | PDF engines rendering inlined SVG |
| Polyglot or mislabeled bytes (HTML in a "PNG") | data URIs, CID attachments |
| `javascript:` / `data:` / protocol-relative URLs, attribute break-out | `<img src>`, `<link href>` |
| Header injection (CR/LF in the name) | email `From` display names, subjects |
| Display spoofing (bidi overrides, invisible-only names, line separators) | email `From` names, PWA names, titles |
| CSS injection through colour values | CSS custom properties, inline styles |
| Path traversal through slot names | provider lookups keyed by slot |

## Mitigations

### Inlining rule and SVG

Owner decision Q-O4: SVG is never uploaded; vectors exist only as committed config default assets.

- `BrandAsset` refuses an `Uploaded` asset whose media type is not `image/png`, `image/jpeg` or `image/webp`.
- `BrandAsset::isInlineSafe()` is true **only** for `BrandAssetOrigin::ConfigDefault` (a trusted file committed to the
  host repository), never for an upload, whatever its type. An SVG is therefore never inline-safe unless it is a
  committed default asset. Consumers may inline bytes into HTML, SVG or PDF markup only when it is true (spec 220,
  section 7.5); MailKit's helper and host PDF renderers enforce it.
- `BrandAssetDataUri` takes the media type from the bytes (PNG, JPEG, WebP magic numbers), never from metadata. Bytes
  that are not a recognised raster are refused unless the asset is inline-safe, so an uploaded asset can only ever be
  embedded as a raster image.

### URLs

`BrandAsset` accepts only a root-relative path (`/…`, not `//…`) or an `https://host[:port]` URL, at most 2048
characters of printable ASCII, without whitespace, quotes, angle brackets, backticks, backslashes, commas or
parentheses; no userinfo. The host is dot-separated non-empty labels (`[A-Za-z0-9-]`) and the port, when present, is
1–65535 without a leading zero. No `javascript:`, `data:`, `http:` or relative URL passes. Without commas and whitespace
a URL is also a safe unquoted `srcset` candidate. Consumers still escape it for its context; it is never meant for a
CSS `url()` (spec 220, section 3.2).

### Text fields

`name`, `shortName`, a string `eyebrow` and `wordmark` must be valid UTF-8 and are refused when they:

- are blank or render as nothing: only Unicode whitespace (NBSP, U+3000, …) and format characters (U+200B, U+FEFF,
  U+2060, ZWJ / ZWNJ on their own);
- contain control characters (C0 and C1, including CR, LF and tab), so they cannot split an email header or a manifest
  line;
- contain bidirectional embedding / override controls (U+202A–U+202E) or isolates (U+2066–U+2069), the classic `From`
  name spoofing trick, or a line / paragraph separator (U+2028, U+2029).

ZWJ and ZWNJ inside text stay allowed (emoji sequences, Persian and Indic scripts), as do the LRM / RLM marks. Accepted
values are trimmed of leading and trailing Unicode whitespace, zero-width space, word joiner and BOM. They are not
HTML-escaped: consumers escape them for their output context as usual.

### Colours

`BrandColors` accepts only `#rrggbb` (normalised to lower case), so a colour can never carry `;`, `}`, `url(` or other
CSS syntax. `ColorMath` works on the same grammar.

### Slots

Slot names are a closed list plus `extra.<id>` with `<id>` matching `[a-z][a-z0-9_]{0,63}` anchored with `\z`: no dot,
slash or newline reaches a provider's lookup.

## Secrets and cryptography

None. `sha256` on `BrandAsset` is a content hash supplied by the provider (validated as 64 hex characters);
`Brand::$version` is a SHA-256 prefix used as a cache key, not a security token.

## Logging

The package logs nothing. Exception messages may contain the offending value (a colour, a slot, a short name) but
never asset bytes; the URL is not echoed in the unsafe-URL message.

## Dependencies and updates

- Runtime: `symfony/translation-contracts` only (interfaces). QA tools stay in `require-dev`.
- `composer audit` runs in CI on every push and pull request; Dependabot opens weekly Composer and GitHub Actions
  updates.

## Provider and consumer checklist

- Providers: sniff and re-encode uploads before building a `BrandAsset` with origin `Uploaded` (the contract checks the
  declared type, not the bytes); never mark an upload as `ConfigDefault`.
- Readers: never build filesystem paths from slot names or URLs; throw `BrandAssetReadException` without paths or
  content in the message.
- Consumers: inline bytes only when `isInlineSafe()`; otherwise use `<img src>`, a CID attachment or
  `BrandAssetDataUri`. Gate on `isInlineSafe()`, never on an exhaustive `match` over `BrandAssetOrigin` (it may gain
  cases in a minor). Escape text fields for their context.

## Audit status

REQ-SEC-004: batch audit before the 1.0.0 tag (owner rule of 2026-11-07: the package has no I/O, no auth, no
crypto). Batch audit IP (2026-11-08): Pass-Low; IP-L1 (asset type check), IP-L2 (invisible, bidi and separator
characters in text fields) and the informational IP-I1 to IP-I7 are addressed before the tag.
