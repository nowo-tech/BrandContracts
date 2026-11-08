<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use InvalidArgumentException;
use Nowo\Brand\Contract\BrandColors;
use Nowo\Brand\Contract\ColorMath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorMath::class)]
#[CoversClass(BrandColors::class)]
final class ColorMathTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function hexes(): iterable
    {
        yield 'lower' => ['#1f6f54', true];
        yield 'upper' => ['#1F6F54', true];
        yield 'shorthand' => ['#fff', false];
        yield 'alpha' => ['#1f6f54ff', false];
        yield 'no hash' => ['1f6f54', false];
        yield 'named' => ['red', false];
        yield 'rgb()' => ['rgb(0 0 0)', false];
        yield 'trailing newline' => ["#1f6f54\n", false];
        yield 'not hex' => ['#1f6g54', false];
        yield 'empty' => ['', false];
    }

    #[DataProvider('hexes')]
    public function testIsHex(string $color, bool $valid): void
    {
        self::assertSame($valid, ColorMath::isHex($color));
    }

    public function testNormalizeLowerCases(): void
    {
        self::assertSame('#abcdef', ColorMath::normalize('#ABCDEF'));
    }

    public function testNormalizeRefusesNonHex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid colour "#fff": expected #rrggbb.');

        ColorMath::normalize('#fff');
    }

    public function testRgbAndChannels(): void
    {
        self::assertSame([15, 28, 24], ColorMath::rgb('#0f1c18'));
        self::assertSame('15 28 24', ColorMath::rgbChannels('#0F1C18'));
    }

    public function testLuminanceAndContrast(): void
    {
        self::assertSame(0.0, ColorMath::relativeLuminance('#000000'));
        self::assertEqualsWithDelta(1.0, ColorMath::relativeLuminance('#ffffff'), 1e-12);
        self::assertEqualsWithDelta(0.0003035, ColorMath::relativeLuminance('#010101'), 1e-7);
        self::assertEqualsWithDelta(0.2158605, ColorMath::relativeLuminance('#808080'), 1e-7);
        self::assertEqualsWithDelta(21.0, ColorMath::contrastRatio('#000000', '#ffffff'), 1e-9);
        self::assertEqualsWithDelta(21.0, ColorMath::contrastRatio('#ffffff', '#000000'), 1e-9);
        self::assertEqualsWithDelta(1.0, ColorMath::contrastRatio('#1f6f54', '#1F6F54'), 1e-12);
        self::assertEqualsWithDelta(6.0692664, ColorMath::contrastRatio('#1f6f54', '#ffffff'), 1e-6);
    }

    public function testContrastingOfPicksTheHigherRatioAndTheFirstOnATie(): void
    {
        self::assertSame('#ffffff', ColorMath::contrastingOf('#1f6f54', '#FFFFFF', '#0f1c18'));
        self::assertSame('#000000', ColorMath::contrastingOf('#ffffff', '#eeeeee', '#000000'));
        self::assertSame('#abcdef', ColorMath::contrastingOf('#000000', '#ABCDEF', '#abcdef'));
    }

    public function testMixMatchesCssColorMixInSrgb(): void
    {
        self::assertSame('#dee9e4', ColorMath::mix('#1f6f54', 10, '#f3f6f4'));
        self::assertSame('#808080', ColorMath::mix('#000000', 50, '#ffffff'));
        self::assertSame('#010101', ColorMath::mix('#010101', 50, '#000000'), 'halves round up');
        self::assertSame('#ffffff', ColorMath::mix('#000000', 0, '#FFFFFF'));
        self::assertSame('#000000', ColorMath::mix('#000000', 100, '#ffffff'));
    }

    public function testMixRefusesAPercentageOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid mix percentage 101: expected 0 to 100.');

        ColorMath::mix('#000000', 101, '#ffffff');
    }

    public function testMixRefusesANegativePercentage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ColorMath::mix('#000000', -1, '#ffffff');
    }

    public function testShadesOfTheLightPalette(): void
    {
        self::assertSame([
            'primary-hover'    => '#134736',
            'primary-contrast' => '#ffffff',
            'border'           => '#d3d7d5',
            'border-strong'    => '#d3d7d5',
            'accent-soft'      => '#dee9e4',
            'surface-muted'    => '#f3f4f3',
            'paper-accent'     => '#e2ebe7',
            'paper-ink'        => '#e5e9e7',
            'muted'            => '#6f7774',
            'brand-mark'       => '#1f6f54',
        ], ColorMath::shades(Fixtures::light()));
    }

    public function testShadesOfTheDarkPaletteWithABorderStrength(): void
    {
        $shades = ColorMath::shades(Fixtures::dark(), 16);

        self::assertSame('#151c19', $shades['primary-contrast']);
        self::assertSame(ColorMath::mix('#e6eee9', 16, '#0c1210'), $shades['border']);
        self::assertSame('#12221b', $shades['accent-soft']);
        self::assertSame('#929a96', $shades['muted']);
    }

    public function testContrastWarningsPassForBeacon(): void
    {
        self::assertSame([], ColorMath::contrastWarnings(Fixtures::light()));
        self::assertSame([], ColorMath::contrastWarnings(Fixtures::dark()));
    }

    public function testContrastWarningsListEveryFailedCheck(): void
    {
        $grey = new BrandColors('#888888', '#777777', '#b42318', '#b54708', '#999999', '#aaaaaa', '#999999');

        self::assertSame(
            ['text_on_paper', 'text_on_surface', 'primary_contrast_on_primary', 'primary_on_surface'],
            ColorMath::contrastWarnings($grey),
        );
    }
}
