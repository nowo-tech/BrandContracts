<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Guards the Packagist-facing manifest (REQ-PKG-004): name, type, license and the PHP range.
 */
#[CoversNothing]
final class PackageManifestTest extends TestCase
{
    public function testComposerManifestDeclaresThePackage(): void
    {
        $raw = file_get_contents(dirname(__DIR__, 2) . '/composer.json');
        self::assertIsString($raw);

        $manifest = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);

        self::assertSame('nowo-tech/brand-contracts', $manifest['name'] ?? null);
        self::assertSame('library', $manifest['type'] ?? null);
        self::assertSame('MIT', $manifest['license'] ?? null);

        $require = $manifest['require'] ?? null;
        self::assertTrue(is_array($require));
        self::assertSame('>=8.2 <8.6', $require['php'] ?? null);
    }
}
