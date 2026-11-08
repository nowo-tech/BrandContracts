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
use function mb_strtolower;
use function preg_match;
use function preg_match_all;
use function rtrim;
use function sprintf;
use function substr;
use function trim;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The brand of an application instance (spec 220, section 2.3): name, short name, eyebrow, wordmark, colours per mode
 * and the resolved image assets.
 *
 * Built by a {@see BrandProviderInterface}; validated on construction, so a consumer never sees an empty name, a bad
 * colour or an unknown slot. Text fields refuse control characters (they reach email headers and manifests).
 */
final readonly class Brand
{
    /** Longest short name, in grapheme clusters (PWA `short_name`). */
    public const SHORT_NAME_MAX = 12;

    /** Length of {@see self::$version}, in hexadecimal characters. */
    public const VERSION_LENGTH = 16;

    private const CONTROL_PATTERN = '/\p{Cc}/u';

    /** Brand name, never empty. */
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
     * @param array<string, BrandAsset> $assets keyed by slot; each key must equal the asset's slot
     * @param string|null $shortName null for the name cut at 12 grapheme clusters
     * @param string|null $wordmark null for the lower-cased name
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
        $this->assertText('name', $name);
        if ($shortName !== null) {
            $this->assertText('short name', $shortName);
            if ($this->graphemeCount($shortName) > self::SHORT_NAME_MAX) {
                throw new InvalidArgumentException(sprintf('Brand short name "%s" is longer than %d characters.', $shortName, self::SHORT_NAME_MAX));
            }
        }
        if ($eyebrow === '') {
            $eyebrow = null;
        }
        if (is_string($eyebrow)) {
            $this->assertText('eyebrow', $eyebrow);
        }
        if ($wordmark !== null) {
            $this->assertText('wordmark', $wordmark);
        }
        foreach ($assets as $slot => $asset) {
            if ($slot !== $asset->slot) {
                throw new InvalidArgumentException(sprintf('Brand asset key "%s" does not match its slot "%s".', $slot, $asset->slot));
            }
        }

        $this->name      = $name;
        $this->shortName = $shortName ?? $this->cut(trim($name), self::SHORT_NAME_MAX);
        $this->eyebrow   = $eyebrow;
        $this->wordmark  = $wordmark ?? mb_strtolower($name, 'UTF-8');
        $this->light     = $light;
        $this->dark      = $dark;
        $this->assets    = $assets;
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
            if ($mark !== null && $mark->isSquare() && $mark->minSide() >= 512) {
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

    private function assertText(string $field, string $value): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Brand %s must not be empty.', $field));
        }
        // preg_match() returns false on invalid UTF-8 with the u modifier.
        $control = preg_match(self::CONTROL_PATTERN, $value);
        if ($control === false) {
            throw new InvalidArgumentException(sprintf('Brand %s is not valid UTF-8.', $field));
        }
        if ($control === 1) {
            throw new InvalidArgumentException(sprintf('Brand %s must not contain control characters.', $field));
        }
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
