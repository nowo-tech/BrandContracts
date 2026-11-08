<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

use InvalidArgumentException;

use function array_diff;
use function array_keys;
use function count;
use function implode;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * The seven brand colours of one mode (light or dark), `#rrggbb` only (spec 220, sections 3.1 and 3.3).
 *
 * Mapping to UiKit's `AppearancePalette`: primary = accent, primaryDeep = accent_deep, danger = danger,
 * warning = warn, paper = paper, text = ink, surface = surface. Colours are lower-cased on construction.
 */
final readonly class BrandColors
{
    /** Colour names, in order (also the keys of {@see self::toArray()}). */
    public const KEYS = ['primary', 'primaryDeep', 'danger', 'warning', 'paper', 'text', 'surface'];

    /** Main brand colour (UiKit `accent`). */
    public string $primary;

    /** Deeper variant of the brand colour, used for hover and emphasis (UiKit `accent_deep`). */
    public string $primaryDeep;

    /** Danger / error colour. */
    public string $danger;

    /** Warning colour (UiKit `warn`). */
    public string $warning;

    /** Page background. */
    public string $paper;

    /** Body text colour (UiKit `ink`). */
    public string $text;

    /** Card / panel background. */
    public string $surface;

    /**
     * @throws InvalidArgumentException when a colour is not `#rrggbb`
     */
    public function __construct(
        string $primary,
        string $primaryDeep,
        string $danger,
        string $warning,
        string $paper,
        string $text,
        string $surface,
    ) {
        $this->primary     = $this->checked('primary', $primary);
        $this->primaryDeep = $this->checked('primaryDeep', $primaryDeep);
        $this->danger      = $this->checked('danger', $danger);
        $this->warning     = $this->checked('warning', $warning);
        $this->paper       = $this->checked('paper', $paper);
        $this->text        = $this->checked('text', $text);
        $this->surface     = $this->checked('surface', $surface);
    }

    /**
     * Builds the colours from an array keyed by {@see self::KEYS} (every key required, no other key).
     *
     * @param array<string, string> $colors
     *
     * @throws InvalidArgumentException when a key is missing or unknown, or a colour is not `#rrggbb`
     */
    public static function fromArray(array $colors): self
    {
        $given = array_keys($colors);
        if (count($given) !== count(self::KEYS) || array_diff(self::KEYS, $given) !== []) {
            throw new InvalidArgumentException(sprintf('Brand colours need exactly the keys %s; got %s.', implode(', ', self::KEYS), implode(', ', $given)));
        }

        return new self(
            $colors['primary'],
            $colors['primaryDeep'],
            $colors['danger'],
            $colors['warning'],
            $colors['paper'],
            $colors['text'],
            $colors['surface'],
        );
    }

    /**
     * The colours keyed by {@see self::KEYS}, in that order.
     *
     * @return array{primary: string, primaryDeep: string, danger: string, warning: string, paper: string, text: string, surface: string}
     */
    public function toArray(): array
    {
        return [
            'primary'     => $this->primary,
            'primaryDeep' => $this->primaryDeep,
            'danger'      => $this->danger,
            'warning'     => $this->warning,
            'paper'       => $this->paper,
            'text'        => $this->text,
            'surface'     => $this->surface,
        ];
    }

    /**
     * The colour named `$name` (one of {@see self::KEYS}).
     *
     * @throws InvalidArgumentException when `$name` is not a colour name
     */
    public function get(string $name): string
    {
        if (!in_array($name, self::KEYS, true)) {
            throw new InvalidArgumentException(sprintf('Unknown brand colour "%s"; expected one of %s.', $name, implode(', ', self::KEYS)));
        }

        return $this->toArray()[$name];
    }

    /**
     * The colour to put on top of `primary` (buttons): surface or text, whichever contrasts more (surface on a tie).
     * Same rule as UiKit's `--nowo-ui-primary-contrast`.
     */
    public function primaryContrast(): string
    {
        return ColorMath::contrastingOf($this->primary, $this->surface, $this->text);
    }

    /**
     * `$percent` % of `$a` mixed into `$b` in sRGB (see {@see ColorMath::mix()}). `$a` and `$b` are colour names
     * ({@see self::KEYS}) or `#rrggbb` values: `mix('primary', 10, 'paper')` is UiKit's accent-soft.
     *
     * @param int $percent 0 to 100
     *
     * @throws InvalidArgumentException when a name is unknown, a hex is invalid or `$percent` is outside 0..100
     */
    public function mix(string $a, int $percent, string $b): string
    {
        return ColorMath::mix($this->resolve($a), $percent, $this->resolve($b));
    }

    private function resolve(string $color): string
    {
        return str_starts_with($color, '#') ? ColorMath::normalize($color) : $this->get($color);
    }

    private function checked(string $name, string $value): string
    {
        if (!ColorMath::isHex($value)) {
            throw new InvalidArgumentException(sprintf('Invalid brand colour %s "%s": expected #rrggbb.', $name, $value));
        }

        return ColorMath::normalize($value);
    }
}
