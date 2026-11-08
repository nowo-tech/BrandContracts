<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

use RuntimeException;

/**
 * Thrown by a {@see BrandAssetReaderInterface} when the bytes of an asset (or of the requested variant) cannot be
 * read. Messages must name the slot and hash prefix only, never a filesystem path or content.
 */
class BrandAssetReadException extends RuntimeException
{
}
