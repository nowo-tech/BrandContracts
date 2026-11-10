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
     * the ambient brand, as before 1.1): with a provider that is not scoped the scope is ignored and the result is
     * whatever ambient brand the provider sees. When that would be wrong, use {@see self::scopedBrandOrNull()}.
     * Exceptions from the provider are not caught.
     */
    public static function brandFor(BrandProviderInterface $provider, ?BrandScope $scope = null): Brand
    {
        if ($scope instanceof BrandScope && $provider instanceof ScopedBrandProviderInterface) {
            return $provider->getBrandFor($scope);
        }

        return $provider->getBrand();
    }

    /**
     * `$provider->getBrandFor($scope)` when the provider is a {@see ScopedBrandProviderInterface}, null otherwise.
     * Use it when the ambient brand would be wrong (a worker serving several tenants): {@see self::brandFor()}
     * ignores the scope with a provider that is not scoped. Exceptions from the provider are not caught.
     */
    public static function scopedBrandOrNull(BrandProviderInterface $provider, BrandScope $scope): ?Brand
    {
        return $provider instanceof ScopedBrandProviderInterface ? $provider->getBrandFor($scope) : null;
    }
}
