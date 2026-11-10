# Upgrading

## Table of contents

- [To 1.1.0](#to-110)
- [Adopting 1.0.0](#adopting-100)

## To 1.1.0

From **1.0.0** (target **1.1.0**, not yet released): `composer update nowo-tech/brand-contracts`. Nothing to change.

- **Providers** that serve more than one project or tenant may also implement `ScopedBrandProviderInterface`
  (`getBrandFor(BrandScope $scope)`). It extends `BrandProviderInterface`, so the same service keeps being injected
  where `BrandProviderInterface` is expected. Single-brand providers need nothing.
- **Consumers** that render outside a request (Messenger, CLI) and know the project can call
  `BrandLookup::brandFor($provider, new BrandScope($projectId))`. With a provider that is not scoped it returns
  `getBrand()`, exactly as before.

## Adopting 1.0.0

First release. Nothing to migrate:

- **Consumers** (bundles, host renderers): `composer require nowo-tech/brand-contracts`, inject
  `?BrandProviderInterface` as an optional dependency and keep the current configuration as the fallback.
- **Providers**: implement `BrandProviderInterface` (InstanceSettingsBundle 1.5 does it for you). Build uploaded
  assets with `BrandAssetOrigin::Uploaded` and committed files with `ConfigDefault`.
- **Hosts with an email or PDF renderer**: inline asset bytes only when `BrandAsset::isInlineSafe()` is true; use
  `BrandAssetDataUri` or a CID attachment otherwise (see [SECURITY.md](SECURITY.md)).

See [USAGE.md](USAGE.md).
