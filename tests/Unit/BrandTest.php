<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use InvalidArgumentException;
use Nowo\Brand\Contract\Brand;
use Nowo\Brand\Contract\BrandAsset;
use Nowo\Brand\Contract\BrandAssetOrigin;
use Nowo\Brand\Contract\BrandAssetSlot;
use Nowo\Brand\Contract\BrandColors;
use Nowo\Brand\Contract\BrandProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

use function str_repeat;

#[CoversClass(Brand::class)]
#[CoversClass(BrandAsset::class)]
#[CoversClass(BrandAssetSlot::class)]
#[CoversClass(BrandColors::class)]
final class BrandTest extends TestCase
{
    /**
     * @param array<string, BrandAsset> $assets
     */
    private function brand(array $assets = [], string $name = 'Beacon'): Brand
    {
        return new Brand($name, Fixtures::light(), Fixtures::dark(), $assets);
    }

    public function testDefaults(): void
    {
        $brand = $this->brand();

        self::assertSame('Beacon', $brand->name);
        self::assertSame('Beacon', $brand->shortName);
        self::assertSame('beacon', $brand->wordmark);
        self::assertNull($brand->eyebrow);
        self::assertSame([], $brand->assets);
        self::assertFalse($brand->isDefault);
        self::assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $brand->version);
        self::assertSame($brand->light, $brand->colors());
        self::assertSame($brand->dark, $brand->colors(true));
    }

    public function testExplicitValues(): void
    {
        $brand = new Brand('Nowo Tech', Fixtures::light(), Fixtures::dark(), shortName: 'Nowo', eyebrow: 'Status pages', wordmark: 'nowo', isDefault: true);

        self::assertSame('Nowo', $brand->shortName);
        self::assertSame('Status pages', $brand->eyebrow);
        self::assertSame('nowo', $brand->wordmark);
        self::assertTrue($brand->isDefault);
    }

    public function testShortNameDefaultIsCutAtTwelveGraphemes(): void
    {
        self::assertSame('Internationa', $this->brand(name: 'International Widgets')->shortName);
        self::assertSame('Acme', $this->brand(name: 'Acme        Corp')->shortName, 'trailing spaces of the cut are trimmed');
        self::assertSame('Acme', $this->brand(name: '   Acme')->shortName, 'leading spaces are not counted');
        // "é" as e + combining acute is one grapheme; the family emoji is one grapheme of several code points.
        self::assertSame("Cafe\u{301} 👨‍👩‍👧 Loung", $this->brand(name: "Cafe\u{301} 👨‍👩‍👧 Loungeroom")->shortName);
    }

    public function testWordmarkDefaultIsMultibyteLowerCase(): void
    {
        self::assertSame('ÉCOLE', $this->brand(name: 'ÉCOLE')->name);
        self::assertSame('école', $this->brand(name: 'ÉCOLE')->wordmark);
    }

    public function testShortNameOfTwelveGraphemesIsAccepted(): void
    {
        $brand = new Brand('Beacon', Fixtures::light(), Fixtures::dark(), shortName: str_repeat('é', 12));

        self::assertSame(str_repeat('é', 12), $brand->shortName);
    }

    public function testShortNameLongerThanTwelveIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand short name "Thirteen char" is longer than 12 characters.');

        new Brand('Beacon', Fixtures::light(), Fixtures::dark(), shortName: 'Thirteen char');
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function badText(): iterable
    {
        yield 'empty name' => [['name' => ''], 'Brand name must not be empty.'];
        yield 'blank name' => [['name' => "  \t"], 'Brand name must not be empty.'];
        yield 'spaces name' => [['name' => '   '], 'Brand name must not be empty.'];
        yield 'newline in name' => [['name' => "Beacon\r\nBcc: x@example.com"], 'Brand name must not contain control characters.'];
        yield 'invalid UTF-8 name' => [['name' => "Bea\xC3con"], 'Brand name is not valid UTF-8.'];
        yield 'empty short name' => [['shortName' => ''], 'Brand short name must not be empty.'];
        yield 'NUL in short name' => [['shortName' => "Be\0"], 'Brand short name must not contain control characters.'];
        yield 'blank eyebrow' => [['eyebrow' => ' '], 'Brand eyebrow must not be empty.'];
        yield 'newline in eyebrow' => [['eyebrow' => "a\nb"], 'Brand eyebrow must not contain control characters.'];
        yield 'empty wordmark' => [['wordmark' => ''], 'Brand wordmark must not be empty.'];
        yield 'C1 control in wordmark' => [['wordmark' => "be\u{85}acon"], 'Brand wordmark must not contain control characters.'];
        // IP-L2: values that render as nothing.
        yield 'zero-width space name' => [['name' => "\u{200B}"], 'Brand name must not be empty.'];
        yield 'NBSP name' => [['name' => "\u{00A0}"], 'Brand name must not be empty.'];
        yield 'ideographic space name' => [['name' => "\u{3000} \u{2009}"], 'Brand name must not be empty.'];
        yield 'BOM and word joiner short name' => [['shortName' => "\u{FEFF}\u{2060}"], 'Brand short name must not be empty.'];
        yield 'ZWJ only eyebrow' => [['eyebrow' => "\u{200D}\u{200C}"], 'Brand eyebrow must not be empty.'];
        // IP-L2: bidi controls and line / paragraph separators.
        yield 'RLO in name' => [['name' => "Ac\u{202E}me"], 'Brand name must not contain bidirectional controls or line separators.'];
        yield 'LRE in short name' => [['shortName' => "a\u{202A}b"], 'Brand short name must not contain bidirectional controls or line separators.'];
        yield 'RLI in eyebrow' => [['eyebrow' => "a\u{2067}b\u{2069}"], 'Brand eyebrow must not contain bidirectional controls or line separators.'];
        yield 'FSI in wordmark' => [['wordmark' => "a\u{2068}b"], 'Brand wordmark must not contain bidirectional controls or line separators.'];
        yield 'line separator in name' => [['name' => "a\u{2028}b"], 'Brand name must not contain bidirectional controls or line separators.'];
        yield 'paragraph separator in name' => [['name' => "a\u{2029}b"], 'Brand name must not contain bidirectional controls or line separators.'];
        yield 'invalid UTF-8 eyebrow' => [['eyebrow' => "\xFF"], 'Brand eyebrow is not valid UTF-8.'];
    }

    public function testJoinersInsideTextAreAllowed(): void
    {
        // ZWJ in emoji sequences, ZWNJ in Persian; LRM / RLM marks are not embedding controls.
        $brand = new Brand("می\u{200C}خواهم", Fixtures::light(), Fixtures::dark(), shortName: "👩\u{200D}💻", eyebrow: "a\u{200F}b", wordmark: "x\u{200D}y");

        self::assertSame("می\u{200C}خواهم", $brand->name);
        self::assertSame("👩\u{200D}💻", $brand->shortName);
        self::assertSame("a\u{200F}b", $brand->eyebrow);
        self::assertSame("x\u{200D}y", $brand->wordmark);
    }

    public function testTextFieldsAreTrimmed(): void
    {
        $brand = $this->brand(name: " \u{00A0}Beacon\u{200B}\u{3000} ");

        self::assertSame('Beacon', $brand->name);
        self::assertSame('Beacon', $brand->shortName);
        self::assertSame('beacon', $brand->wordmark, 'the default wordmark comes from the trimmed name');
        self::assertSame($this->brand()->version, $brand->version);

        $explicit = new Brand('Beacon', Fixtures::light(), Fixtures::dark(), shortName: ' Bcn ', eyebrow: "\u{3000}Status ", wordmark: ' beacon ');
        self::assertSame('Bcn', $explicit->shortName);
        self::assertSame('Status', $explicit->eyebrow);
        self::assertSame('beacon', $explicit->wordmark);
        self::assertSame('Mid  dle', $this->brand(name: 'Mid  dle')->name, 'inner spaces are kept');
    }

    /**
     * @param array<string, string> $fields
     */
    #[DataProvider('badText')]
    public function testBadTextIsRefused(array $fields, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new Brand(
            $fields['name'] ?? 'Beacon',
            Fixtures::light(),
            Fixtures::dark(),
            shortName: $fields['shortName'] ?? null,
            eyebrow: $fields['eyebrow'] ?? null,
            wordmark: $fields['wordmark'] ?? null,
        );
    }

    public function testAnEmptyEyebrowMeansNone(): void
    {
        self::assertNull((new Brand('Beacon', Fixtures::light(), Fixtures::dark(), eyebrow: ''))->eyebrow);
    }

    public function testAnAssetKeyMustMatchItsSlot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand asset key "logo_dark" does not match its slot "logo_light".');

        $this->brand(['logo_dark' => Fixtures::asset('logo_light')]);
    }

    public function testAnAssetMustBeABrandAsset(): void
    {
        // IP-L1: a look-alike object would skip every BrandAsset rule.
        $fake = new class {
            public string $slot     = 'mark';
            public string $url      = 'javascript:alert(1)';
            public string $mimeType = 'text/html';
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand asset "mark" must be a ' . BrandAsset::class . '; got class@anonymous.');

        new Brand('Beacon', Fixtures::light(), Fixtures::dark(), ['mark' => $fake]);
    }

    public function testANonObjectAssetIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand asset "mark" must be a ' . BrandAsset::class . '; got string.');

        new Brand('Beacon', Fixtures::light(), Fixtures::dark(), ['mark' => '/brand/mark.png']);
    }

    public function testAnIntegerAssetKeyIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand asset key "0" does not match its slot "mark".');

        new Brand('Beacon', Fixtures::light(), Fixtures::dark(), [Fixtures::asset('mark')]);
    }

    public function testATranslatableWithInvalidUtf8StringFormIsRefused(): void
    {
        // IP-I4: an InvalidArgumentException, not a JsonException from the version hash.
        $eyebrow = new class implements TranslatableInterface, Stringable {
            public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return 'x';
            }

            public function __toString(): string
            {
                return "bad\xC3";
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand eyebrow translatable has a string form that is not valid UTF-8.');

        new Brand('Beacon', Fixtures::light(), Fixtures::dark(), eyebrow: $eyebrow);
    }

    public function testAssetReturnsADirectHit(): void
    {
        $assets = [];
        foreach ([...BrandAssetSlot::FIXED, 'extra.mascot'] as $slot) {
            $assets[$slot] = Fixtures::asset($slot);
        }
        $brand = $this->brand($assets);

        foreach ($assets as $slot => $asset) {
            self::assertSame($asset, $brand->asset($slot), $slot);
        }
    }

    public function testFallbackChains(): void
    {
        $light   = Fixtures::asset('logo_light', 1200, 400);
        $appIcon = Fixtures::asset('app_icon');
        $brand   = $this->brand(['logo_light' => $light, 'app_icon' => $appIcon]);

        self::assertSame($light, $brand->asset('logo_dark'));
        self::assertSame($appIcon, $brand->asset('mark'));
        self::assertSame($appIcon, $brand->asset('favicon'));
        self::assertNull($brand->asset('extra.mascot'));

        $mark     = Fixtures::asset('mark', 64, 64);
        $withMark = $this->brand(['mark' => $mark, 'app_icon' => $appIcon]);
        self::assertSame($mark, $withMark->asset('favicon'), 'favicon prefers mark over app_icon');
    }

    public function testNothingResolvesOnAnEmptyBrand(): void
    {
        $brand = $this->brand();

        foreach (BrandAssetSlot::FIXED as $slot) {
            self::assertNull($brand->asset($slot), $slot);
        }
    }

    /**
     * @return iterable<string, array{?int, ?int, bool}>
     */
    public static function marksForAppIcon(): iterable
    {
        yield 'square 512' => [512, 512, true];
        yield 'square 1024' => [1024, 1024, true];
        yield 'nearly square' => [520, 512, true];
        yield 'square 511' => [511, 511, false];
        yield 'not square' => [800, 512, false];
        yield 'unknown size (SVG)' => [null, null, false];
    }

    #[DataProvider('marksForAppIcon')]
    public function testAppIconFallsBackToALargeSquareMark(?int $width, ?int $height, bool $used): void
    {
        $mark  = Fixtures::asset('mark', $width, $height, BrandAssetOrigin::ConfigDefault);
        $brand = $this->brand(['mark' => $mark]);

        self::assertSame($used ? $mark : null, $brand->asset('app_icon'));
    }

    public function testAssetRefusesAnUnknownSlot(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->brand()->asset('banner');
    }

    public function testVersionIsStableAndChangesWithEveryField(): void
    {
        $base = $this->brand(['mark' => Fixtures::asset('mark')]);
        $same = new Brand('Beacon', Fixtures::light(), Fixtures::dark(), ['mark' => Fixtures::asset('mark')], shortName: 'Beacon', wordmark: 'beacon');
        self::assertSame($base->version, $same->version, 'explicit values equal to the defaults give the same version');

        $variants = [
            'name'      => $this->brand(['mark' => Fixtures::asset('mark')], 'Beacon 2'),
            'shortName' => new Brand('Beacon', Fixtures::light(), Fixtures::dark(), ['mark' => Fixtures::asset('mark')], shortName: 'Bcn'),
            'eyebrow'   => new Brand('Beacon', Fixtures::light(), Fixtures::dark(), ['mark' => Fixtures::asset('mark')], eyebrow: 'Hi'),
            'wordmark'  => new Brand('Beacon', Fixtures::light(), Fixtures::dark(), ['mark' => Fixtures::asset('mark')], wordmark: 'BEACON'),
            'light'     => new Brand('Beacon', Fixtures::dark(), Fixtures::dark(), ['mark' => Fixtures::asset('mark')]),
            'dark'      => new Brand('Beacon', Fixtures::light(), Fixtures::light(), ['mark' => Fixtures::asset('mark')]),
            'asset'     => $this->brand(['mark' => Fixtures::asset('mark', 256, 256)]),
            'no asset'  => $this->brand(),
            'isDefault' => new Brand('Beacon', Fixtures::light(), Fixtures::dark(), ['mark' => Fixtures::asset('mark')], isDefault: true),
        ];
        foreach ($variants as $field => $brand) {
            self::assertNotSame($base->version, $brand->version, $field);
        }
    }

    public function testVersionIgnoresAssetOrder(): void
    {
        $a = $this->brand(['mark' => Fixtures::asset('mark'), 'favicon' => Fixtures::asset('favicon')]);
        $b = $this->brand(['favicon' => Fixtures::asset('favicon'), 'mark' => Fixtures::asset('mark')]);

        self::assertSame($a->version, $b->version);
    }

    public function testTranslatableEyebrow(): void
    {
        $eyebrow = new class implements TranslatableInterface, Stringable {
            public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return $translator->trans('brand.eyebrow_default', [], 'messages', $locale);
            }

            public function __toString(): string
            {
                return 'brand.eyebrow_default';
            }
        };
        $translator = new class implements TranslatorInterface {
            use TranslatorTrait;

            /**
             * @param array<string, mixed> $parameters
             */
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return ('es' === ($locale ?? $this->getLocale()) ? 'Estado' : 'Status') . ':' . $id;
            }
        };
        $brand = new Brand('Beacon', Fixtures::light(), Fixtures::dark(), eyebrow: $eyebrow);

        self::assertSame($eyebrow, $brand->eyebrow);
        self::assertSame('Status:brand.eyebrow_default', $brand->eyebrowText($translator));
        self::assertSame('Estado:brand.eyebrow_default', $brand->eyebrowText($translator, 'es'));
        self::assertNotSame($this->brand()->version, $brand->version);
        self::assertSame('Plain', (new Brand('Beacon', Fixtures::light(), Fixtures::dark(), eyebrow: 'Plain'))->eyebrowText($translator));
        self::assertNull($this->brand()->eyebrowText($translator));
    }

    public function testATranslatableWithoutStringFormStillHasAVersion(): void
    {
        $eyebrow = new class implements TranslatableInterface {
            public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return 'x';
            }
        };

        self::assertMatchesRegularExpression('/^[0-9a-f]{16}$/', (new Brand('Beacon', Fixtures::light(), Fixtures::dark(), eyebrow: $eyebrow))->version);
    }

    public function testAHostProviderIsAFewLines(): void
    {
        $provider = new class implements BrandProviderInterface {
            public function getBrand(): Brand
            {
                return new Brand('Acme', Fixtures::light(), Fixtures::dark(), isDefault: true);
            }
        };

        self::assertSame('acme', $provider->getBrand()->wordmark);
    }
}
