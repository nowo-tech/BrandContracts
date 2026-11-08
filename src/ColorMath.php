<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

use InvalidArgumentException;

use function hexdec;
use function max;
use function min;
use function preg_match;
use function round;
use function sprintf;
use function strtolower;
use function substr;

/**
 * Colour math on `#rrggbb` hex colours: WCAG luminance and contrast, sRGB mixes and the derived shades of the UiKit
 * token set (spec 220, sections 3.2 and 3.3).
 *
 * For renderers that cannot use CSS `color-mix()` (email clients, PDF engines, the PWA manifest): {@see self::mix()}
 * gives the same proportions as `color-mix(in srgb, a p%, b)`. Pure static functions, no state.
 */
final class ColorMath
{
    /** WCAG AA minimum for normal text (1.4.3). */
    public const AA_TEXT = 4.5;

    /** WCAG AA minimum for non-text contrast (1.4.11). */
    public const AA_NON_TEXT = 3.0;

    /** Default share of ink in the border colour, in percent (UiKit `BorderStrength::Medium`, light mode). */
    public const DEFAULT_BORDER_PERCENT = 14;

    private const HEX_PATTERN = '/^#[0-9a-fA-F]{6}\z/';

    /**
     * Whether `$color` is a `#rrggbb` hex colour (either case). Shorthand, alpha and named colours are not.
     */
    public static function isHex(string $color): bool
    {
        return preg_match(self::HEX_PATTERN, $color) === 1;
    }

    /**
     * `$color` lower-cased, after checking it is `#rrggbb`.
     *
     * @throws InvalidArgumentException when `$color` is not `#rrggbb`
     */
    public static function normalize(string $color): string
    {
        if (!self::isHex($color)) {
            throw new InvalidArgumentException(sprintf('Invalid colour "%s": expected #rrggbb.', $color));
        }

        return strtolower($color);
    }

    /**
     * The red, green and blue channels (0 to 255) of `$color`.
     *
     * @throws InvalidArgumentException when `$color` is not `#rrggbb`
     *
     * @return array{int, int, int}
     */
    public static function rgb(string $color): array
    {
        $hex = self::normalize($color);

        return [
            (int) hexdec(substr($hex, 1, 2)),
            (int) hexdec(substr($hex, 3, 2)),
            (int) hexdec(substr($hex, 5, 2)),
        ];
    }

    /**
     * The channels as `"r g b"` (the format of UiKit's `--nowo-ui-shadow-rgb`).
     *
     * @throws InvalidArgumentException when `$color` is not `#rrggbb`
     */
    public static function rgbChannels(string $color): string
    {
        [$r, $g, $b] = self::rgb($color);

        return sprintf('%d %d %d', $r, $g, $b);
    }

    /**
     * WCAG 2 relative luminance, 0 (black) to 1 (white). Uses the 0.03928 threshold, as UiKit's emitter does.
     *
     * @throws InvalidArgumentException when `$color` is not `#rrggbb`
     */
    public static function relativeLuminance(string $color): float
    {
        $linear = [];
        foreach (self::rgb($color) as $channel) {
            $v        = $channel / 255;
            $linear[] = $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }

    /**
     * WCAG 2 contrast ratio between two colours, 1 to 21 (order does not matter).
     *
     * @throws InvalidArgumentException when a colour is not `#rrggbb`
     */
    public static function contrastRatio(string $a, string $b): float
    {
        $la = self::relativeLuminance($a);
        $lb = self::relativeLuminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Of `$a` and `$b`, the one with the higher contrast against `$background` (`$a` on a tie), lower-cased. Same rule
     * as UiKit's `--nowo-ui-primary-contrast`.
     *
     * @throws InvalidArgumentException when a colour is not `#rrggbb`
     */
    public static function contrastingOf(string $background, string $a, string $b): string
    {
        return self::contrastRatio($background, $a) >= self::contrastRatio($background, $b)
            ? self::normalize($a)
            : self::normalize($b);
    }

    /**
     * `$percent` % of `$a` mixed into `$b` in sRGB, as CSS `color-mix(in srgb, a p%, b)` does for opaque colours.
     * Each channel is rounded to the nearest integer (halves up). The result is lower-case `#rrggbb`.
     *
     * @param int $percent 0 (only `$b`) to 100 (only `$a`)
     *
     * @throws InvalidArgumentException when a colour is not `#rrggbb` or `$percent` is outside 0..100
     */
    public static function mix(string $a, int $percent, string $b): string
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException(sprintf('Invalid mix percentage %d: expected 0 to 100.', $percent));
        }
        $ca  = self::rgb($a);
        $cb  = self::rgb($b);
        $out = '#';
        for ($i = 0; $i < 3; ++$i) {
            $out .= sprintf('%02x', (int) round(($ca[$i] * $percent + $cb[$i] * (100 - $percent)) / 100));
        }

        return $out;
    }

    /**
     * The derived shades of one mode, in hex, keyed by the UiKit token name without `--nowo-ui-` (section 3.2):
     * `primary-hover`, `primary-contrast`, `border`, `border-strong`, `accent-soft`, `surface-muted`, `paper-accent`,
     * `paper-ink`, `muted`, `brand-mark`.
     *
     * The shadow channels are not included: UiKit uses the ink channels in light mode and `0 0 0` in dark mode
     * ({@see self::rgbChannels()}).
     *
     * @param int $borderPercent share of ink in the border (UiKit border strength: 7 / 14 / 26 light, 9 / 16 / 30 dark)
     *
     * @throws InvalidArgumentException when `$borderPercent` is outside 0..100
     *
     * @return array<string, string>
     */
    public static function shades(BrandColors $colors, int $borderPercent = self::DEFAULT_BORDER_PERCENT): array
    {
        $border = self::mix($colors->text, $borderPercent, $colors->paper);

        return [
            'primary-hover'    => $colors->primaryDeep,
            'primary-contrast' => $colors->primaryContrast(),
            'border'           => $border,
            'border-strong'    => $border,
            'accent-soft'      => self::mix($colors->primary, 10, $colors->paper),
            'surface-muted'    => self::mix($colors->text, 5, $colors->surface),
            'paper-accent'     => self::mix($colors->primary, 8, $colors->paper),
            'paper-ink'        => self::mix($colors->text, 6, $colors->paper),
            'muted'            => self::mix($colors->text, 60, $colors->surface),
            'brand-mark'       => $colors->primary,
        ];
    }

    /**
     * The advisory contrast checks of section 3.3 that `$colors` fails, as codes (empty when every check passes):
     * `text_on_paper` and `text_on_surface` (below 4.5), `primary_contrast_on_primary` (below 4.5), `primary_on_surface`
     * (below 3). Advisory only: a provider shows a notice, it never refuses the colours (owner decision Q-O8).
     *
     * @return list<string>
     */
    public static function contrastWarnings(BrandColors $colors): array
    {
        $checks = [
            'text_on_paper'               => [$colors->text, $colors->paper, self::AA_TEXT],
            'text_on_surface'             => [$colors->text, $colors->surface, self::AA_TEXT],
            'primary_contrast_on_primary' => [$colors->primaryContrast(), $colors->primary, self::AA_TEXT],
            'primary_on_surface'          => [$colors->primary, $colors->surface, self::AA_NON_TEXT],
        ];
        $failed = [];
        foreach ($checks as $code => [$fg, $bg, $min]) {
            if (self::contrastRatio($fg, $bg) < $min) {
                $failed[] = $code;
            }
        }

        return $failed;
    }
}
