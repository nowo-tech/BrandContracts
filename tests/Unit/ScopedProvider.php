<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use Nowo\Brand\Contract\Brand;
use Nowo\Brand\Contract\BrandScope;
use Nowo\Brand\Contract\ScopedBrandProviderInterface;

/**
 * Host provider of the tests: ambient brand "Ambient", scoped brand "Brand of {id}".
 */
final class ScopedProvider implements ScopedBrandProviderInterface
{
    public static function brand(string $name): Brand
    {
        return new Brand(name: $name, light: Fixtures::light(), dark: Fixtures::dark(), isDefault: false);
    }

    public function getBrand(): Brand
    {
        return self::brand('Ambient');
    }

    public function getBrandFor(BrandScope $scope): Brand
    {
        return self::brand('Brand of ' . $scope->id);
    }
}
