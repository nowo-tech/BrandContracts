<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use InvalidArgumentException;
use Nowo\Brand\Contract\BrandColors;
use Nowo\Brand\Contract\ColorMath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BrandColors::class)]
#[CoversClass(ColorMath::class)]
final class BrandColorsTest extends TestCase
{
    public function testColoursAreLowerCased(): void
    {
        $colors = new BrandColors('#1F6F54', '#134736', '#B42318', '#b54708', '#F3F6F4', '#0f1c18', '#FFFFFF');

        self::assertSame('#1f6f54', $colors->primary);
        self::assertSame('#b42318', $colors->danger);
        self::assertSame('#f3f6f4', $colors->paper);
        self::assertSame('#ffffff', $colors->surface);
    }

    public function testEveryColourIsValidated(): void
    {
        foreach (BrandColors::KEYS as $index => $key) {
            $values       = Fixtures::light()->toArray();
            $values[$key] = 'red';
            try {
                BrandColors::fromArray($values);
                self::fail('Expected a refusal for ' . $key);
            } catch (InvalidArgumentException $e) {
                self::assertSame('Invalid brand colour ' . $key . ' "red": expected #rrggbb.', $e->getMessage(), (string) $index);
            }
        }
    }

    public function testToArrayAndFromArrayRoundTrip(): void
    {
        $light = Fixtures::light();

        self::assertSame(BrandColors::KEYS, array_keys($light->toArray()));
        self::assertEquals($light, BrandColors::fromArray($light->toArray()));
        self::assertEquals($light, BrandColors::fromArray(array_reverse($light->toArray(), true)));
    }

    public function testFromArrayRefusesAMissingKey(): void
    {
        $values = Fixtures::light()->toArray();
        unset($values['surface']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand colours need exactly the keys primary, primaryDeep, danger, warning, paper, text, surface; got primary, primaryDeep, danger, warning, paper, text.');

        BrandColors::fromArray($values);
    }

    public function testFromArrayRefusesAnExtraKey(): void
    {
        $values            = Fixtures::light()->toArray();
        $values['success'] = '#00ff00';

        $this->expectException(InvalidArgumentException::class);

        BrandColors::fromArray($values);
    }

    public function testFromArrayRefusesARenamedKey(): void
    {
        $values = Fixtures::light()->toArray();
        unset($values['text']);
        $values['ink'] = '#0f1c18';

        $this->expectException(InvalidArgumentException::class);

        BrandColors::fromArray($values);
    }

    public function testGet(): void
    {
        self::assertSame('#134736', Fixtures::light()->get('primaryDeep'));
    }

    public function testGetRefusesAnUnknownName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown brand colour "accent"');

        Fixtures::light()->get('accent');
    }

    public function testPrimaryContrast(): void
    {
        self::assertSame('#ffffff', Fixtures::light()->primaryContrast());
        self::assertSame('#151c19', Fixtures::dark()->primaryContrast());
    }

    public function testMixAcceptsNamesAndHex(): void
    {
        $light = Fixtures::light();

        self::assertSame('#dee9e4', $light->mix('primary', 10, 'paper'));
        self::assertSame('#dee9e4', $light->mix('#1F6F54', 10, 'paper'));
        self::assertSame('#808080', $light->mix('#000000', 50, '#ffffff'));
    }

    public function testMixRefusesAnUnknownName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Fixtures::light()->mix('ink', 10, 'paper');
    }

    public function testMixRefusesABadHex(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Fixtures::light()->mix('#fff', 10, 'paper');
    }
}
