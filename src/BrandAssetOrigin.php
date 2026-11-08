<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

/**
 * Where a brand asset comes from. Decides whether its bytes may be inlined ({@see BrandAsset::isInlineSafe()}).
 */
enum BrandAssetOrigin: string
{
    /** Uploaded by an administrator: untrusted, raster only (PNG, JPEG, WebP), never inlined. */
    case Uploaded = 'uploaded';

    /** A file committed to the host repository and named in configuration: trusted, may be SVG or ICO. */
    case ConfigDefault = 'config_default';
}
