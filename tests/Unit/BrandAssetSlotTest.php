<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use InvalidArgumentException;
use Nowo\Brand\Contract\BrandAssetSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function str_repeat;

#[CoversClass(BrandAssetSlot::class)]
final class BrandAssetSlotTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function slots(): iterable
    {
        yield 'logo_light' => ['logo_light', true];
        yield 'logo_dark' => ['logo_dark', true];
        yield 'mark' => ['mark', true];
        yield 'app_icon' => ['app_icon', true];
        yield 'favicon' => ['favicon', true];
        yield 'extra' => ['extra.mascot', true];
        yield 'extra with digits and underscore' => ['extra.email_mascot2', true];
        yield 'extra at 64' => ['extra.a' . str_repeat('b', 63), true];
        yield 'extra over 64' => ['extra.a' . str_repeat('b', 64), false];
        yield 'bare prefix' => ['extra.', false];
        yield 'extra upper case' => ['extra.Mascot', false];
        yield 'extra starting with digit' => ['extra.1mascot', false];
        yield 'extra with dot' => ['extra.a.b', false];
        yield 'extra with slash' => ['extra.../etc', false];
        yield 'extra trailing newline' => ["extra.mascot\n", false];
        yield 'unknown' => ['banner', false];
        yield 'empty' => ['', false];
        yield 'case differs' => ['LOGO_LIGHT', false];
    }

    #[DataProvider('slots')]
    public function testIsValid(string $slot, bool $valid): void
    {
        self::assertSame($valid, BrandAssetSlot::isValid($slot));
    }

    public function testIsExtraOnlyForExtraSlots(): void
    {
        self::assertTrue(BrandAssetSlot::isExtra('extra.mascot'));
        self::assertFalse(BrandAssetSlot::isExtra('mark'));
        self::assertFalse(BrandAssetSlot::isExtra('extras.mascot'));
    }

    public function testExtraBuildsTheSlotName(): void
    {
        self::assertSame('extra.email_mascot', BrandAssetSlot::extra('email_mascot'));
    }

    public function testExtraRefusesABadId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid extra brand image id "Bad Id"');

        BrandAssetSlot::extra('Bad Id');
    }

    public function testExtraRefusesATrailingNewline(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BrandAssetSlot::extra("mascot\n");
    }

    public function testAssertValidAcceptsAKnownSlot(): void
    {
        $this->expectNotToPerformAssertions();

        BrandAssetSlot::assertValid(BrandAssetSlot::FAVICON);
    }

    public function testAssertValidRefusesAnUnknownSlot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown brand asset slot "banner".');

        BrandAssetSlot::assertValid('banner');
    }

    public function testFixedListsTheFiveSlotsInOrder(): void
    {
        self::assertSame(['logo_light', 'logo_dark', 'mark', 'app_icon', 'favicon'], BrandAssetSlot::FIXED);
    }
}
