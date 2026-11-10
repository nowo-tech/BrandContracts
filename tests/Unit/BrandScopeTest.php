<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use InvalidArgumentException;
use Nowo\Brand\Contract\Brand;
use Nowo\Brand\Contract\BrandLookup;
use Nowo\Brand\Contract\BrandProviderInterface;
use Nowo\Brand\Contract\BrandScope;
use Nowo\Brand\Contract\BrandScopeResolverInterface;
use Nowo\Brand\Contract\ScopedBrandProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function serialize;
use function sprintf;
use function str_repeat;
use function strlen;
use function unserialize;

#[CoversClass(BrandScope::class)]
#[CoversClass(BrandLookup::class)]
final class BrandScopeTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validIds(): iterable
    {
        yield 'uuid' => ['0193a5e4-7c1b-7d2e-9f00-3a6c1d2e4f50'];
        yield 'slug' => ['acme-corp'];
        yield 'numeric' => ['42'];
        yield 'one char' => ['x'];
        yield 'longest' => [str_repeat('a', BrandScope::MAX_ID_LENGTH)];
    }

    #[DataProvider('validIds')]
    public function testAcceptsPrintableIds(string $id): void
    {
        self::assertSame($id, (new BrandScope($id))->id);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'space' => ['acme corp'];
        yield 'newline' => ["acme\n"];
        yield 'control' => ["acme\x00"];
        yield 'non ascii' => ['açme'];
        yield 'too long' => [str_repeat('a', BrandScope::MAX_ID_LENGTH + 1)];
    }

    #[DataProvider('invalidIds')]
    public function testRefusesOtherIds(string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid brand scope id');

        new BrandScope($id);
    }

    public function testEquality(): void
    {
        self::assertTrue((new BrandScope('a'))->equals(new BrandScope('a')));
        self::assertFalse((new BrandScope('a'))->equals(new BrandScope('b')));
    }

    public function testLookupWithoutScopeUsesTheAmbientBrand(): void
    {
        $provider = new ScopedProvider();

        self::assertSame('Ambient', BrandLookup::brandFor($provider)->name);
    }

    public function testLookupWithScopeUsesTheScopedBrand(): void
    {
        self::assertSame('Brand of acme', BrandLookup::brandFor(new ScopedProvider(), new BrandScope('acme'))->name);
    }

    public function testLookupFallsBackToGetBrandForAnUnscopedProvider(): void
    {
        $provider = new class implements BrandProviderInterface {
            public function getBrand(): Brand
            {
                return ScopedProvider::brand('Instance');
            }
        };

        self::assertSame('Instance', BrandLookup::brandFor($provider, new BrandScope('acme'))->name);
    }

    public function testScopedBrandOrNull(): void
    {
        $unscoped = new class implements BrandProviderInterface {
            public function getBrand(): Brand
            {
                return ScopedProvider::brand('Instance');
            }
        };

        self::assertSame('Brand of acme', BrandLookup::scopedBrandOrNull(new ScopedProvider(), new BrandScope('acme'))?->name);
        self::assertNull(BrandLookup::scopedBrandOrNull($unscoped, new BrandScope('acme')));
    }

    public function testSerializationRoundTrip(): void
    {
        $scope = unserialize(serialize(new BrandScope('acme')));

        self::assertInstanceOf(BrandScope::class, $scope);
        self::assertSame('acme', $scope->id);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function craftedPayloads(): iterable
    {
        $class = BrandScope::class;
        $len   = strlen($class);

        yield 'invalid id' => [sprintf('O:%d:"%s":1:{s:2:"id";s:7:"bad id ";}', $len, $class)];
        yield 'missing id' => [sprintf('O:%d:"%s":0:{}', $len, $class)];
        yield 'non-string id' => [sprintf('O:%d:"%s":1:{s:2:"id";i:5;}', $len, $class)];
    }

    #[DataProvider('craftedPayloads')]
    public function testUnserializeRevalidates(string $payload): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid brand scope id');

        unserialize($payload, ['allowed_classes' => [BrandScope::class]]);
    }

    public function testResolverDrivenProvider(): void
    {
        $resolver = new class implements BrandScopeResolverInterface {
            public ?BrandScope $scope = null;

            public function currentScope(): ?BrandScope
            {
                return $this->scope;
            }
        };
        $provider = new class($resolver) implements ScopedBrandProviderInterface {
            public function __construct(private readonly BrandScopeResolverInterface $resolver)
            {
            }

            public function getBrand(): Brand
            {
                $scope = $this->resolver->currentScope();

                return $scope instanceof BrandScope ? $this->getBrandFor($scope) : ScopedProvider::brand('Instance');
            }

            public function getBrandFor(BrandScope $scope): Brand
            {
                return ScopedProvider::brand('Brand of ' . $scope->id);
            }
        };

        self::assertSame('Instance', $provider->getBrand()->name);
        $resolver->scope = new BrandScope('beta');
        self::assertSame('Brand of beta', $provider->getBrand()->name);
        self::assertSame('Brand of gamma', BrandLookup::brandFor($provider, new BrandScope('gamma'))->name);
    }
}
