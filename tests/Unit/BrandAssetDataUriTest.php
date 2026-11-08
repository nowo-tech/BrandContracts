<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use InvalidArgumentException;
use Nowo\Brand\Contract\BrandAsset;
use Nowo\Brand\Contract\BrandAssetDataUri;
use Nowo\Brand\Contract\BrandAssetOrigin;
use Nowo\Brand\Contract\BrandAssetReaderInterface;
use Nowo\Brand\Contract\BrandAssetReadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function base64_encode;
use function hash;

#[CoversClass(BrandAssetDataUri::class)]
#[CoversClass(BrandAsset::class)]
final class BrandAssetDataUriTest extends TestCase
{
    private const PNG  = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR";
    private const JPEG = "\xFF\xD8\xFF\xE0\x00\x10JFIF";
    private const WEBP = "RIFF\x24\x00\x00\x00WEBPVP8 ";
    private const SVG  = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function sniffing(): iterable
    {
        yield 'png' => [self::PNG, 'image/png'];
        yield 'jpeg' => [self::JPEG, 'image/jpeg'];
        yield 'webp' => [self::WEBP, 'image/webp'];
        yield 'riff not webp' => ["RIFF\x24\x00\x00\x00WAVEfmt ", null];
        yield 'riff too short' => ['RIFF1234WEB', null];
        yield 'svg' => [self::SVG, null];
        yield 'gif' => ['GIF89a', null];
        yield 'png signature cut' => ["\x89PNG\r\n", null];
        yield 'empty' => ['', null];
    }

    #[DataProvider('sniffing')]
    public function testSniffRaster(string $bytes, ?string $type): void
    {
        self::assertSame($type, BrandAssetDataUri::sniffRaster($bytes));
    }

    public function testAnUploadedRasterBecomesADataUriOfItsSniffedType(): void
    {
        // The asset says PNG, the bytes are a JPEG variant: the bytes decide.
        $asset = Fixtures::asset('mark');

        self::assertSame('data:image/jpeg;base64,' . base64_encode(self::JPEG), BrandAssetDataUri::fromBytes($asset, self::JPEG));
        self::assertSame('image/png', BrandAssetDataUri::mimeTypeOf($asset, self::PNG));
    }

    public function testAnUploadWhoseBytesAreNotRasterIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The bytes of uploaded brand asset "mark" are not PNG, JPEG or WebP; refusing to embed them.');

        BrandAssetDataUri::fromBytes(Fixtures::asset('mark'), self::SVG);
    }

    public function testAConfigDefaultSvgKeepsItsDeclaredType(): void
    {
        $svg = new BrandAsset('mark', '/brand/mark.svg', 'image/svg+xml', null, null, hash('sha256', self::SVG), BrandAssetOrigin::ConfigDefault);

        self::assertSame('data:image/svg+xml;base64,' . base64_encode(self::SVG), BrandAssetDataUri::fromBytes($svg, self::SVG));
    }

    public function testEmptyBytesAreRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty bytes for brand asset "favicon".');

        BrandAssetDataUri::mimeTypeOf(Fixtures::asset('favicon', origin: BrandAssetOrigin::ConfigDefault), '');
    }

    public function testWithAReader(): void
    {
        $reader = new class implements BrandAssetReaderInterface {
            public function read(BrandAsset $asset, ?int $variant = null): string
            {
                if ($variant !== null && $variant !== 192) {
                    throw new BrandAssetReadException('No variant ' . $variant . ' for ' . $asset->slot . '.');
                }

                return "\x89PNG\r\n\x1a\n" . $asset->slot . $variant;
            }
        };
        $icon = Fixtures::asset('app_icon');

        self::assertStringStartsWith('data:image/png;base64,', BrandAssetDataUri::fromBytes($icon, $reader->read($icon, 192)));

        $this->expectException(BrandAssetReadException::class);
        $reader->read($icon, 180);
    }
}
