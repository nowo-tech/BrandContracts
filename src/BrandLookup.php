<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

/**
 * Consumer helper: resolve a brand for an optional scope with any provider (DDD P2, 1.1).
 */
final class BrandLookup
{
    /**
     * `$provider->getBrandFor($scope)` when a scope is given and the provider is a
     * {@see ScopedBrandProviderInterface}; `$provider->getBrand()` otherwise (no scope, or a provider that only knows
     * the ambient brand, as before 1.1). Exceptions from the provider are not caught.
     */
    public static function brandFor(BrandProviderInterface $provider, ?BrandScope $scope = null): Brand
    {
        if ($scope instanceof BrandScope && $provider instanceof ScopedBrandProviderInterface) {
            return $provider->getBrandFor($scope);
        }

        return $provider->getBrand();
    }
}
