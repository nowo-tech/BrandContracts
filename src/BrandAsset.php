<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

use InvalidArgumentException;

use function abs;
use function in_array;
use function max;
use function min;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strtolower;

/**
 * One resolved brand image: where it is served, what it is and where it comes from (spec 220, sections 2.3 and 4).
 *
 * The URL is already safe for an `<img src>` / `<link href>` attribute: a root-relative path (`/brand/logo.png`) or an
 * `https://` URL, printable ASCII without quotes, angle brackets, backslashes or whitespace. Anything else is refused
 * on construction, so consumers can print it after their usual attribute escaping.
 *
 * Uploaded assets are raster only (PNG, JPEG, WebP) and never inline-safe; config default assets (committed files) may
 * be any image type, SVG and ICO included, and are inline-safe (owner decision Q-O4).
 */
final readonly class BrandAsset
{
    /** The only types an uploaded asset may have (spec 220, section 5.2). */
    public const UPLOAD_MIME_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    /** SVG media type: inline-safe only as a config default asset. */
    public const SVG_MIME_TYPE = 'image/svg+xml';

    /** Longest URL accepted. */
    public const MAX_URL_LENGTH = 2048;

    /** Tolerance of {@see self::isSquare()}: the sides may differ by this share of the longer side. */
    public const SQUARE_TOLERANCE = 0.02;

    private const MIME_PATTERN   = '/^image\/[a-z0-9][a-z0-9.+-]{0,63}\z/';
    private const SHA256_PATTERN = '/^[0-9a-f]{64}\z/';
    private const URL_FORBIDDEN  = '/[^\x21-\x7e]|["\'<>\\\\`]/';
    private const HTTPS_PATTERN  = '/^https:\/\/[A-Za-z0-9.-]+(?::[0-9]{1,5})?(?:[\/?#]|\z)/';

    /** Slot name ({@see BrandAssetSlot}). */
    public string $slot;

    /** Root-relative path or `https://` URL. */
    public string $url;

    /** Media type, lower-case (`image/png`, `image/svg+xml`, ...). */
    public string $mimeType;

    /** Width in pixels, null when unknown (SVG). */
    public ?int $width;

    /** Height in pixels, null when unknown (SVG). */
    public ?int $height;

    /** SHA-256 of the served bytes, lower-case hex. */
    public string $sha256;

    /** Uploaded or config default. */
    public BrandAssetOrigin $origin;

    /**
     * @throws InvalidArgumentException when a value breaks the rules above
     */
    public function __construct(
        string $slot,
        string $url,
        string $mimeType,
        ?int $width,
        ?int $height,
        string $sha256,
        BrandAssetOrigin $origin,
    ) {
        BrandAssetSlot::assertValid($slot);
        $this->assertSafeUrl($url);

        $mimeType = strtolower($mimeType);
        if (preg_match(self::MIME_PATTERN, $mimeType) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid brand asset media type "%s": expected image/*.', $mimeType));
        }
        if ($origin === BrandAssetOrigin::Uploaded && !in_array($mimeType, self::UPLOAD_MIME_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('An uploaded brand asset must be PNG, JPEG or WebP; got "%s".', $mimeType));
        }
        if (($width !== null && $width < 1) || ($height !== null && $height < 1)) {
            throw new InvalidArgumentException('Brand asset dimensions must be positive or null.');
        }
        $sha256 = strtolower($sha256);
        if (preg_match(self::SHA256_PATTERN, $sha256) !== 1) {
            throw new InvalidArgumentException('Invalid brand asset sha256: expected 64 hexadecimal characters.');
        }

        $this->slot     = $slot;
        $this->url      = $url;
        $this->mimeType = $mimeType;
        $this->width    = $width;
        $this->height   = $height;
        $this->sha256   = $sha256;
        $this->origin   = $origin;
    }

    /**
     * Whether the bytes of this asset may be inlined into HTML, SVG or PDF markup (spec 220, section 7.5): true only
     * for a config default asset (a trusted, committed file), never for an upload, whatever its type. Everything
     * else goes in as an image reference: `<img src>`, a CID attachment or a raster data URI ({@see BrandAssetDataUri}).
     */
    public function isInlineSafe(): bool
    {
        return $this->origin === BrandAssetOrigin::ConfigDefault;
    }

    /**
     * Whether the asset is an SVG image.
     */
    public function isSvg(): bool
    {
        return $this->mimeType === self::SVG_MIME_TYPE;
    }

    /**
     * Whether both dimensions are known and differ by at most 2 % of the longer side.
     */
    public function isSquare(): bool
    {
        if ($this->width === null || $this->height === null) {
            return false;
        }

        return abs($this->width - $this->height) <= self::SQUARE_TOLERANCE * max($this->width, $this->height);
    }

    /**
     * The shorter known side in pixels, null when a dimension is unknown.
     */
    public function minSide(): ?int
    {
        if ($this->width === null || $this->height === null) {
            return null;
        }

        return min($this->width, $this->height);
    }

    /**
     * Whether `$url` is a root-relative path or an `https://` URL that {@see self::__construct()} accepts.
     */
    public static function isSafeUrl(string $url): bool
    {
        if ($url === '' || strlen($url) > self::MAX_URL_LENGTH || preg_match(self::URL_FORBIDDEN, $url) === 1) {
            return false;
        }
        if (str_starts_with($url, '/')) {
            return !str_starts_with($url, '//');
        }

        return preg_match(self::HTTPS_PATTERN, $url) === 1;
    }

    private function assertSafeUrl(string $url): void
    {
        if (!self::isSafeUrl($url)) {
            throw new InvalidArgumentException('Unsafe brand asset URL: expected a root-relative path or an https:// URL in printable ASCII without quotes, angle brackets, backslashes or whitespace.');
        }
    }
}
