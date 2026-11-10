<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

/**
 * A brand provider that can also resolve the brand of an explicit scope (DDD P2, 1.1).
 *
 * {@see BrandProviderInterface::getBrand()} resolves the brand from ambient state (the request, the current tenant).
 * Messenger handlers, console commands and other code without that state call {@see self::getBrandFor()} with the
 * project or tenant they render for. Consumers keep injecting {@see BrandProviderInterface} and use the scoped call
 * when the provider supports it ({@see BrandLookup::brandFor()} does the check).
 *
 * The same rules as `getBrand()` apply: cheap to call several times, memoised per scope at most per request or
 * message, may throw when backed by a database.
 */
interface ScopedBrandProviderInterface extends BrandProviderInterface
{
    /**
     * The brand of `$scope`. Never a brand with an empty name: for a scope with nothing stored (unknown scope
     * included), return the provider's defaults with {@see Brand::$isDefault} set, as `getBrand()` does.
     */
    public function getBrandFor(BrandScope $scope): Brand;
}
