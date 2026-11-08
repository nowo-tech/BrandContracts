<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

/**
 * Source of the brand of an application instance (spec 220, section 2.3).
 *
 * Implemented by InstanceSettingsBundle (`InstanceBrandProvider`) or by a small host class. The package ships no
 * default provider: consumers inject this interface as an optional dependency and keep their own configuration as the
 * fallback when it is absent.
 *
 * Implementations should be cheap to call several times per request (memoise per request, and reset the memo between
 * requests in a long-running worker). Consumers that render error pages should wrap the call: a provider backed by a
 * database may throw.
 */
interface BrandProviderInterface
{
    /**
     * The current brand. Never a brand with an empty name: a provider with nothing stored returns its defaults with
     * {@see Brand::$isDefault} set.
     */
    public function getBrand(): Brand;
}
