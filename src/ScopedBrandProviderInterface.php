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
 * The scope selects, it does not authorize: `getBrandFor()` is not an access check. Callers derive the scope from
 * trusted state (a message the application created, an operator's console option, the route owner after the access
 * check), never from raw request input. Serve only publishable brand data from it: `isDefault` and `version` already
 * tell an existing scope from an unknown one.
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
