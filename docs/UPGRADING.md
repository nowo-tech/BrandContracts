# Upgrading

## Table of contents

- [Adopting 1.0.0](#adopting-100)

## Adopting 1.0.0

First release. Nothing to migrate:

- **Consumers** (bundles, host renderers): `composer require nowo-tech/brand-contracts`, inject
  `?BrandProviderInterface` as an optional dependency and keep the current configuration as the fallback.
- **Providers**: implement `BrandProviderInterface` (InstanceSettingsBundle 1.5 does it for you). Build uploaded
  assets with `BrandAssetOrigin::Uploaded` and committed files with `ConfigDefault`.
- **Hosts with an email or PDF renderer**: inline asset bytes only when `BrandAsset::isInlineSafe()` is true; use
  `BrandAssetDataUri` or a CID attachment otherwise (see [SECURITY.md](SECURITY.md)).

See [USAGE.md](USAGE.md).
