<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

use InvalidArgumentException;

use function in_array;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Names of the brand image slots (spec 220, section 4.1).
 *
 * Five fixed slots plus host-declared extra images named `extra.<id>`, where `<id>` is lower-case ASCII letters,
 * digits and underscores, starting with a letter, at most 64 characters (`extra.mascot`, `extra.email_mascot`).
 */
final class BrandAssetSlot
{
    /** Horizontal lockup for light backgrounds. */
    public const LOGO_LIGHT = 'logo_light';

    /** Horizontal lockup for dark backgrounds. */
    public const LOGO_DARK = 'logo_dark';

    /** Square brand mark. */
    public const MARK = 'mark';

    /** Square application icon (PWA icons, apple-touch-icon). */
    public const APP_ICON = 'app_icon';

    /** Square favicon. */
    public const FAVICON = 'favicon';

    /** Prefix of host-declared extra image slots. */
    public const EXTRA_PREFIX = 'extra.';

    /** The fixed slots, in display order. */
    public const FIXED = [
        self::LOGO_LIGHT,
        self::LOGO_DARK,
        self::MARK,
        self::APP_ICON,
        self::FAVICON,
    ];

    private const EXTRA_ID_PATTERN = '/^[a-z][a-z0-9_]{0,63}\z/';

    /**
     * Whether `$slot` is a fixed slot or a well-formed `extra.<id>` slot.
     */
    public static function isValid(string $slot): bool
    {
        return in_array($slot, self::FIXED, true) || self::isExtra($slot);
    }

    /**
     * Whether `$slot` is a well-formed `extra.<id>` slot.
     */
    public static function isExtra(string $slot): bool
    {
        return str_starts_with($slot, self::EXTRA_PREFIX)
            && preg_match(self::EXTRA_ID_PATTERN, substr($slot, strlen(self::EXTRA_PREFIX))) === 1;
    }

    /**
     * The slot name of the extra image `$id` (`mascot` gives `extra.mascot`).
     *
     * @throws InvalidArgumentException when `$id` is not lower-case ASCII letters, digits and underscores
     */
    public static function extra(string $id): string
    {
        if (preg_match(self::EXTRA_ID_PATTERN, $id) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid extra brand image id "%s": use lower-case letters, digits and underscores, starting with a letter (at most 64).', $id));
        }

        return self::EXTRA_PREFIX . $id;
    }

    /**
     * Throws unless `$slot` is valid ({@see self::isValid()}).
     *
     * @throws InvalidArgumentException
     */
    public static function assertValid(string $slot): void
    {
        if (!self::isValid($slot)) {
            throw new InvalidArgumentException(sprintf('Unknown brand asset slot "%s".', $slot));
        }
    }
}
