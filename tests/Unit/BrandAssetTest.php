<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use InvalidArgumentException;
use Nowo\Brand\Contract\BrandAsset;
use Nowo\Brand\Contract\BrandAssetOrigin;
use Nowo\Brand\Contract\BrandAssetSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function hash;
use function str_repeat;
use function strtoupper;

#[CoversClass(BrandAsset::class)]
#[CoversClass(BrandAssetSlot::class)]
final class BrandAssetTest extends TestCase
{
    public function testHoldsItsValues(): void
    {
        $sha   = hash('sha256', 'logo');
        $asset = new BrandAsset('logo_light', 'https://cdn.example.com/logo.png?v=2', 'IMAGE/PNG', 1200, 400, strtoupper($sha), BrandAssetOrigin::Uploaded);

        self::assertSame('logo_light', $asset->slot);
        self::assertSame('https://cdn.example.com/logo.png?v=2', $asset->url);
        self::assertSame('image/png', $asset->mimeType);
        self::assertSame(1200, $asset->width);
        self::assertSame(400, $asset->height);
        self::assertSame($sha, $asset->sha256);
        self::assertSame(BrandAssetOrigin::Uploaded, $asset->origin);
        self::assertFalse($asset->isSvg());
        self::assertFalse($asset->isSquare());
        self::assertSame(400, $asset->minSide());
    }

    public function testUploadsAreNeverInlineSafe(): void
    {
        foreach (BrandAsset::UPLOAD_MIME_TYPES as $type) {
            self::assertFalse(Fixtures::asset('mark', mimeType: $type)->isInlineSafe(), $type);
        }
    }

    public function testConfigDefaultsAreInlineSafeSvgIncluded(): void
    {
        $svg = new BrandAsset('mark', '/brand/beacon-mark.svg', 'image/svg+xml', null, null, hash('sha256', 'svg'), BrandAssetOrigin::ConfigDefault);

        self::assertTrue($svg->isInlineSafe());
        self::assertTrue($svg->isSvg());
        self::assertFalse($svg->isSquare());
        self::assertNull($svg->minSide());
    }

    public function testAnUploadedSvgIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('An uploaded brand asset must be PNG, JPEG or WebP; got "image/svg+xml".');

        new BrandAsset('mark', '/x.svg', 'image/svg+xml', null, null, hash('sha256', 'x'), BrandAssetOrigin::Uploaded);
    }

    public function testAnUploadedIcoIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BrandAsset('favicon', '/favicon.ico', 'image/x-icon', 32, 32, hash('sha256', 'x'), BrandAssetOrigin::Uploaded);
    }

    public function testAConfigDefaultIcoIsAccepted(): void
    {
        $ico = new BrandAsset('favicon', '/favicon.ico', 'image/vnd.microsoft.icon', 32, 32, hash('sha256', 'x'), BrandAssetOrigin::ConfigDefault);

        self::assertSame('image/vnd.microsoft.icon', $ico->mimeType);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function badMimeTypes(): iterable
    {
        yield 'text' => ['text/html'];
        yield 'empty subtype' => ['image/'];
        yield 'parameter' => ['image/png; charset=utf-8'];
        yield 'newline' => ["image/png\n"];
        yield 'empty' => [''];
    }

    #[DataProvider('badMimeTypes')]
    public function testABadMimeTypeIsRefused(string $type): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BrandAsset('mark', '/x.png', $type, 1, 1, hash('sha256', 'x'), BrandAssetOrigin::ConfigDefault);
    }

    public function testAnUnknownSlotIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown brand asset slot "banner".');

        Fixtures::asset('banner');
    }

    public function testAnExtraSlotIsAccepted(): void
    {
        self::assertSame('extra.email_mascot', Fixtures::asset('extra.email_mascot')->slot);
    }

    /**
     * @return iterable<string, array{?int, ?int}>
     */
    public static function badDimensions(): iterable
    {
        yield 'zero width' => [0, 10];
        yield 'negative height' => [10, -1];
        yield 'zero height, null width' => [null, 0];
    }

    #[DataProvider('badDimensions')]
    public function testNonPositiveDimensionsAreRefused(?int $width, ?int $height): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand asset dimensions must be positive or null.');

        Fixtures::asset('mark', $width, $height);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function badHashes(): iterable
    {
        yield 'short' => ['abc'];
        yield 'not hex' => [str_repeat('g', 64)];
        yield 'trailing newline' => [str_repeat('a', 64) . "\n"];
        yield 'too long' => [str_repeat('a', 65)];
    }

    #[DataProvider('badHashes')]
    public function testABadHashIsRefused(string $sha): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid brand asset sha256');

        new BrandAsset('mark', '/x.png', 'image/png', 1, 1, $sha, BrandAssetOrigin::Uploaded);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function urls(): iterable
    {
        yield 'path' => ['/_settings-file/brand/logo_light/0123456789ab.png', true];
        yield 'root' => ['/', true];
        yield 'https' => ['https://cdn.example.com/brand/logo.png', true];
        yield 'https with port' => ['https://cdn.example.com:8443/logo.png', true];
        yield 'https host only' => ['https://cdn.example.com', true];
        yield 'https query' => ['https://cdn.example.com?x=1', true];
        yield 'percent-encoded' => ['/brand/logo%20light.png', true];
        yield 'protocol-relative' => ['//evil.example.com/x.png', false];
        yield 'http' => ['http://cdn.example.com/logo.png', false];
        yield 'javascript' => ['javascript:alert(1)', false];
        yield 'data' => ['data:image/png;base64,AAAA', false];
        yield 'relative' => ['brand/logo.png', false];
        yield 'userinfo' => ['https://user@cdn.example.com/logo.png', false];
        yield 'empty host' => ['https:///logo.png', false];
        yield 'upper-case scheme' => ['HTTPS://cdn.example.com/logo.png', false];
        yield 'space' => ['/brand/logo light.png', false];
        yield 'newline' => ["/brand/logo.png\n", false];
        yield 'double quote' => ['/brand/"onerror=x.png', false];
        yield 'single quote' => ["/brand/'x.png", false];
        yield 'angle bracket' => ['/brand/<script>.png', false];
        yield 'backslash' => ['/\\evil.example.com/x.png', false];
        yield 'backtick' => ['/brand/`x.png', false];
        yield 'non-ASCII' => ['/brand/logó.png', false];
        yield 'empty' => ['', false];
        yield 'too long' => ['/' . str_repeat('a', BrandAsset::MAX_URL_LENGTH), false];
        yield 'longest' => ['/' . str_repeat('a', BrandAsset::MAX_URL_LENGTH - 1), true];
        // IP-I3: srcset and CSS url() separators.
        yield 'comma' => ['/brand/a,b.png', false];
        yield 'srcset candidate' => ['https://cdn.example.com/a.png,b 2x', false];
        yield 'parentheses' => ['/brand/a(b).png', false];
        yield 'semicolon' => ['/brand/a;b.png', true];
        // IP-I3: host labels and port range.
        yield 'host a dot' => ['https://.', false];
        yield 'host leading dot' => ['https://.example.com/', false];
        yield 'host trailing dot' => ['https://example.com./', false];
        yield 'host empty label' => ['https://cdn..example.com/', false];
        yield 'single-label host' => ['https://localhost/logo.png', true];
        yield 'port 1' => ['https://cdn.example.com:1/', true];
        yield 'port 65535' => ['https://cdn.example.com:65535/', true];
        yield 'port 65536' => ['https://cdn.example.com:65536/', false];
        yield 'port 99999' => ['https://cdn.example.com:99999/', false];
        yield 'port 0' => ['https://cdn.example.com:0/', false];
        yield 'port leading zero' => ['https://cdn.example.com:0443/', false];
        yield 'empty port' => ['https://cdn.example.com:/', false];
        yield 'port 60000' => ['https://cdn.example.com:60000', true];
        yield 'port 65499' => ['https://cdn.example.com:65499', true];
        yield 'port 65529' => ['https://cdn.example.com:65529', true];
    }

    #[DataProvider('urls')]
    public function testUrlSafety(string $url, bool $safe): void
    {
        self::assertSame($safe, BrandAsset::isSafeUrl($url));
    }

    public function testAnUnsafeUrlIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsafe brand asset URL');

        new BrandAsset('mark', 'javascript:alert(1)', 'image/png', 1, 1, hash('sha256', 'x'), BrandAssetOrigin::Uploaded);
    }

    /**
     * @return iterable<string, array{?int, ?int, bool}>
     */
    public static function squares(): iterable
    {
        yield 'exact' => [512, 512, true];
        yield 'within 2 %' => [500, 510, true];
        yield 'beyond 2 %' => [500, 511, false];
        yield 'wide' => [1200, 400, false];
        yield 'unknown width' => [null, 512, false];
        yield 'unknown height' => [512, null, false];
    }

    #[DataProvider('squares')]
    public function testIsSquare(?int $width, ?int $height, bool $square): void
    {
        self::assertSame($square, Fixtures::asset('mark', $width, $height)->isSquare());
    }

    public function testMinSideWithAnUnknownHeight(): void
    {
        self::assertNull(Fixtures::asset('mark', 512, null)->minSide());
    }
}
