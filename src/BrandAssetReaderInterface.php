<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

/**
 * Reads the bytes of a brand asset, for consumers that embed it instead of linking its URL: a CID attachment in an
 * email, a data URI or an image object in a PDF (spec 220, sections 2.3 and 7.5).
 *
 * Implemented by the provider that knows where assets live (InstanceSettingsBundle reads its database store and the
 * config default files). Before inlining the bytes into HTML, SVG or PDF markup, a consumer must check
 * {@see BrandAsset::isInlineSafe()}; everything else goes in as an image reference ({@see BrandAssetDataUri}).
 */
interface BrandAssetReaderInterface
{
    /**
     * The bytes of `$asset`, or of its generated square variant of `$variant` pixels (for example 180, 192, 512, 32,
     * 16; variants are PNG).
     *
     * @param int|null $variant null for the asset itself
     *
     * @throws BrandAssetReadException when the asset or the variant is unavailable
     */
    public function read(BrandAsset $asset, ?int $variant = null): string;
}
