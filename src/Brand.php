<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

use InvalidArgumentException;
use Stringable;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_slice;
use function count;
use function get_debug_type;
use function hash;
use function implode;
use function is_string;
use function json_encode;
use function ksort;
use function mb_check_encoding;
use function mb_strtolower;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function rtrim;
use function sprintf;
use function substr;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The brand of an application instance (spec 220, section 2.3): name, short name, eyebrow, wordmark, colours per mode
 * and the resolved image assets.
 *
 * Built by a {@see BrandProviderInterface}; validated on construction, so a consumer never sees an empty name, a bad
 * colour or an unknown slot. Text fields are trimmed and refuse values that are blank or invisible, control characters,
 * bidirectional embedding / override / isolate controls and line or paragraph separators (they reach email headers,
 * manifests and window titles).
 */
final readonly class Brand
{
    /** Longest short name, in grapheme clusters (PWA `short_name`). */
    public const SHORT_NAME_MAX = 12;

    /** Length of {@see self::$version}, in hexadecimal characters. */
    public const VERSION_LENGTH = 16;

    /** C0 and C1 controls (CR, LF, NUL, U+0085, ...). */
    private const CONTROL_PATTERN = '/\p{Cc}/u';

    /** Bidi embeddings and overrides (U+202A-U+202E), isolates (U+2066-U+2069), line and paragraph separators. */
    private const BIDI_OR_SEPARATOR_PATTERN = '/[\x{202A}-\x{202E}\x{2066}-\x{2069}\p{Zl}\p{Zp}]/u';

    /** Characters that render as nothing: Unicode spaces and format characters (U+200B, U+FEFF, ZWJ, ...). */
    private const INVISIBLE_PATTERN = '/[\s\p{Z}\p{Cf}]+/u';

    /** Leading and trailing Unicode whitespace (NBSP and U+3000 included), zero-width space, word joiner and BOM. */
    private const TRIM_PATTERN = '/^[\s\p{Z}\x{200B}\x{2060}\x{FEFF}]+|[\s\p{Z}\x{200B}\x{2060}\x{FEFF}]+\z/u';

    /** Brand name, trimmed, never blank. */
    public string $name;

    /** Short name, at most 12 grapheme clusters; defaults to the name cut at 12. */
    public string $shortName;

    /** Line above the name; null for none. A translatable when the default is a translation key. */
    public string|TranslatableInterface|null $eyebrow;

    /** Text logo; defaults to the lower-cased name. */
    public string $wordmark;

    /** Colours for light mode. */
    public BrandColors $light;

    /** Colours for dark mode (independent palette, never derived from light). */
    public BrandColors $dark;

    /**
     * Resolved assets keyed by slot (only slots that have an upload or a config default).
     *
     * @var array<string, BrandAsset>
     */
    public array $assets;

    /** True while nothing is stored and every value is a default. */
    public bool $isDefault;

    /** Short hash of every field above, for ETags and cache keys. */
    public string $version;

    /**
     * Text values are trimmed of leading and trailing Unicode whitespace (NBSP and zero-width space included) before
     * they are stored and checked.
     *
     * @param array<string, BrandAsset> $assets keyed by slot; each value must be a BrandAsset whose slot equals its key
     * @param string|null $shortName null for the name cut at 12 grapheme clusters
     * @param string|null $wordmark null for the lower-cased name
     *
     * @phpstan-param array<mixed> $assets
     *
     * @throws InvalidArgumentException when a value breaks the rules of the class
     */
    public function __construct(
        string $name,
        BrandColors $light,
        BrandColors $dark,
        array $assets = [],
        ?string $shortName = null,
        string|TranslatableInterface|null $eyebrow = null,
        ?string $wordmark = null,
        bool $isDefault = false,
    ) {
        $name = $this->text('name', $name);
        if ($shortName !== null) {
            $shortName = $this->text('short name', $shortName);
            if ($this->graphemeCount($shortName) > self::SHORT_NAME_MAX) {
                throw new InvalidArgumentException(sprintf('Brand short name "%s" is longer than %d characters.', $shortName, self::SHORT_NAME_MAX));
            }
        }
        if ($eyebrow === '') {
            $eyebrow = null;
        }
        if (is_string($eyebrow)) {
            $eyebrow = $this->text('eyebrow', $eyebrow);
        } elseif ($eyebrow instanceof Stringable && !mb_check_encoding((string) $eyebrow, 'UTF-8')) {
            throw new InvalidArgumentException('Brand eyebrow translatable has a string form that is not valid UTF-8.');
        }
        if ($wordmark !== null) {
            $wordmark = $this->text('wordmark', $wordmark);
        }
        $checked = [];
        foreach ($assets as $slot => $asset) {
            if (!$asset instanceof BrandAsset) {
                throw new InvalidArgumentException(sprintf('Brand asset "%s" must be a %s; got %s.', $slot, BrandAsset::class, get_debug_type($asset)));
            }
            if ($slot !== $asset->slot) {
                throw new InvalidArgumentException(sprintf('Brand asset key "%s" does not match its slot "%s".', $slot, $asset->slot));
            }
            $checked[$asset->slot] = $asset;
        }

        $this->name      = $name;
        $this->shortName = $shortName ?? $this->cut($name, self::SHORT_NAME_MAX);
        $this->eyebrow   = $eyebrow;
        $this->wordmark  = $wordmark ?? mb_strtolower($name, 'UTF-8');
        $this->light     = $light;
        $this->dark      = $dark;
        $this->assets    = $checked;
        $this->isDefault = $isDefault;
        $this->version   = $this->computeVersion();
    }

    /**
     * The asset for `$slot` after the fallback chain of spec 220, section 4.2, or null when nothing resolves (the
     * consumer then renders its own fallback: mark + name text, the name initial, its configured icons):
     *
     * - `logo_light`: itself;
     * - `logo_dark`: itself, then `logo_light`;
     * - `mark`: itself, then `app_icon`;
     * - `app_icon`: itself, then `mark` when square and at least 512 px;
     * - `favicon`: itself, then `mark`, then `app_icon`;
     * - `extra.<id>`: itself.
     *
     * The returned asset keeps its own slot, so a consumer can tell a fallback from a direct hit.
     *
     * @throws InvalidArgumentException when `$slot` is not a valid slot name
     */
    public function asset(string $slot): ?BrandAsset
    {
        BrandAssetSlot::assertValid($slot);

        $chain = match ($slot) {
            BrandAssetSlot::LOGO_DARK => [BrandAssetSlot::LOGO_DARK, BrandAssetSlot::LOGO_LIGHT],
            BrandAssetSlot::MARK      => [BrandAssetSlot::MARK, BrandAssetSlot::APP_ICON],
            BrandAssetSlot::FAVICON   => [BrandAssetSlot::FAVICON, BrandAssetSlot::MARK, BrandAssetSlot::APP_ICON],
            default                   => [$slot],
        };
        foreach ($chain as $candidate) {
            if (isset($this->assets[$candidate])) {
                return $this->assets[$candidate];
            }
        }

        if ($slot === BrandAssetSlot::APP_ICON) {
            $mark = $this->assets[BrandAssetSlot::MARK] ?? null;
            if ($mark instanceof BrandAsset && $mark->isSquare() && $mark->minSide() >= 512) {
                return $mark;
            }
        }

        return null;
    }

    /**
     * The colours of the light or the dark mode.
     */
    public function colors(bool $dark = false): BrandColors
    {
        return $dark ? $this->dark : $this->light;
    }

    /**
     * The eyebrow as text: a string as is, a translatable through `$translator` (in `$locale`, or the translator's
     * current locale). Null when there is no eyebrow.
     */
    public function eyebrowText(TranslatorInterface $translator, ?string $locale = null): ?string
    {
        if ($this->eyebrow instanceof TranslatableInterface) {
            return $this->eyebrow->trans($translator, $locale);
        }

        return $this->eyebrow;
    }

    private function computeVersion(): string
    {
        $assets = [];
        foreach ($this->assets as $slot => $asset) {
            $assets[$slot] = [$asset->url, $asset->mimeType, $asset->width, $asset->height, $asset->sha256, $asset->origin->value];
        }
        ksort($assets);

        $eyebrow = $this->eyebrow;
        if ($eyebrow instanceof TranslatableInterface) {
            // A translatable is identified by its class and, when it has one, its string form (Symfony's
            // TranslatableMessage gives its message key). The rendered text also depends on the locale.
            $eyebrow = ['t', get_debug_type($eyebrow), $eyebrow instanceof Stringable ? (string) $eyebrow : null];
        }

        $payload = json_encode(
            [1, $this->name, $this->shortName, $eyebrow, $this->wordmark, $this->light->toArray(), $this->dark->toArray(), $assets, $this->isDefault],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return substr(hash('sha256', $payload), 0, self::VERSION_LENGTH);
    }

    /**
     * Checks a text field and returns it trimmed.
     */
    private function text(string $field, string $value): string
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            throw new InvalidArgumentException(sprintf('Brand %s is not valid UTF-8.', $field));
        }
        // Valid UTF-8 from here on, so the /u patterns below cannot fail.
        if ((string) preg_replace(self::INVISIBLE_PATTERN, '', $value) === '') {
            throw new InvalidArgumentException(sprintf('Brand %s must not be empty.', $field));
        }
        if (preg_match(self::CONTROL_PATTERN, $value) === 1) {
            throw new InvalidArgumentException(sprintf('Brand %s must not contain control characters.', $field));
        }
        if (preg_match(self::BIDI_OR_SEPARATOR_PATTERN, $value) === 1) {
            throw new InvalidArgumentException(sprintf('Brand %s must not contain bidirectional controls or line separators.', $field));
        }

        return (string) preg_replace(self::TRIM_PATTERN, '', $value);
    }

    /**
     * @return list<string>
     */
    private function graphemes(string $value): array
    {
        preg_match_all('/\X/u', $value, $matches);

        return $matches[0];
    }

    private function graphemeCount(string $value): int
    {
        return count($this->graphemes($value));
    }

    private function cut(string $value, int $max): string
    {
        return rtrim(implode('', array_slice($this->graphemes($value), 0, $max)));
    }
}
