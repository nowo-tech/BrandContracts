<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

/**
 * Ambient brand scope of the current request, message or command (DDD P2, 1.1).
 *
 * Optional port for providers: a {@see ScopedBrandProviderInterface} can implement `getBrand()` as
 * `getBrandFor($resolver->currentScope())` and keep the lookup in one place. The host implements it from what it has
 * (the route's project, a Messenger stamp, a console option). Return null when there is no scope; the provider then
 * returns its instance-wide brand.
 */
interface BrandScopeResolverInterface
{
    /**
     * The current scope, or null when there is none.
     */
    public function currentScope(): ?BrandScope;
}
