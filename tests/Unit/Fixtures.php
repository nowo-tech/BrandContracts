<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use Nowo\Brand\Contract\BrandAsset;
use Nowo\Brand\Contract\BrandAssetOrigin;
use Nowo\Brand\Contract\BrandColors;

use function hash;

/**
 * Shared test data: symfony-beacon's palette (spec 220, section 3.1).
 */
final class Fixtures
{
    public static function light(): BrandColors
    {
        return new BrandColors('#1f6f54', '#134736', '#b42318', '#b54708', '#f3f6f4', '#0f1c18', '#ffffff');
    }

    public static function dark(): BrandColors
    {
        return new BrandColors('#4aad7f', '#6bc49a', '#f97066', '#fdb022', '#0c1210', '#e6eee9', '#151c19');
    }

    public static function asset(
        string $slot,
        ?int $width = 512,
        ?int $height = 512,
        BrandAssetOrigin $origin = BrandAssetOrigin::Uploaded,
        string $mimeType = 'image/png',
    ): BrandAsset {
        return new BrandAsset($slot, '/_settings-file/brand/' . $slot . '.png', $mimeType, $width, $height, hash('sha256', $slot), $origin);
    }
}
