# Configuration

The package has **no configuration**: it ships interfaces and value objects only, and no service definitions.

- A **provider** decides where the brand comes from (stored settings, config, a constant) and builds a `Brand`.
- A **consumer** asks for `BrandProviderInterface` as an optional dependency (Symfony: `nullOnInvalid` /
  `?BrandProviderInterface $brand = null`) and keeps its own configuration as the fallback when no provider exists.

Wiring is the job of the bundles that provide or consume the contract; see [USAGE.md](USAGE.md).
