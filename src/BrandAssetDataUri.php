<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

use InvalidArgumentException;

use function base64_encode;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Builds `data:` URIs from the bytes a {@see BrandAssetReaderInterface} returns, applying the inlining rule of spec
 * 220, section 7.5, and gives the sniffed raster type for CID attachments.
 *
 * The media type comes from the bytes, never from the asset's metadata: PNG, JPEG and WebP are recognised by their
 * magic bytes. Any other content (SVG, ICO, GIF, ...) is accepted only from an inline-safe (config default) asset, with
 * its declared type. An uploaded asset whose bytes are not a recognised raster is refused.
 */
final class BrandAssetDataUri
{
    /**
     * `data:<type>;base64,<bytes>` for `$bytes`, read from `$asset` (or one of its variants).
     *
     * @throws InvalidArgumentException when `$bytes` is empty, or is not PNG, JPEG or WebP and the asset is not inline-safe
     */
    public static function fromBytes(BrandAsset $asset, string $bytes): string
    {
        return sprintf('data:%s;base64,%s', self::mimeTypeOf($asset, $bytes), base64_encode($bytes));
    }

    /**
     * The media type to declare for `$bytes` read from `$asset` (data URI, CID attachment), by the rule above.
     *
     * @throws InvalidArgumentException when `$bytes` is empty, or is not PNG, JPEG or WebP and the asset is not inline-safe
     */
    public static function mimeTypeOf(BrandAsset $asset, string $bytes): string
    {
        if ($bytes === '') {
            throw new InvalidArgumentException(sprintf('Empty bytes for brand asset "%s".', $asset->slot));
        }
        $raster = self::sniffRaster($bytes);
        if ($raster !== null) {
            return $raster;
        }
        if ($asset->isInlineSafe()) {
            return $asset->mimeType;
        }

        throw new InvalidArgumentException(sprintf('The bytes of uploaded brand asset "%s" are not PNG, JPEG or WebP; refusing to embed them.', $asset->slot));
    }

    /**
     * `image/png`, `image/jpeg` or `image/webp` when the magic bytes say so, otherwise null.
     */
    public static function sniffRaster(string $bytes): ?string
    {
        if (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (strlen($bytes) >= 12 && str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return null;
    }
}
