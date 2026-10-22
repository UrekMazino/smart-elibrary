<?php

namespace App\Services;

use App\Models\User;

/**
 * How the admin looks for one staff member: colour theme, status colours, text size, motion, font.
 *
 * Each person may choose their own (users.appearance, set under Appearance in the profile menu);
 * everyone else follows the library default (settings 'appearance_default', set under Utilities →
 * Theme). The admin layout puts the result on <html> (AdminLayout: data-* attributes plus the colour
 * variables), which public/css/admin-theme.css (generated from the template, see
 * scripts/admin-theme.mjs) and public/css/admin-appearance.css read.
 *
 * Colours are checked against WCAG 2.2: white text on the accent (buttons) and the accent as text on
 * white (links) need 4.5:1, so a custom accent, and the colour-blind-safe status colours, are darkened
 * until they reach it; the dark sidebar gets a lighter variant of the accent that reaches 4.5:1 there.
 */
class Appearance
{
    /** key => [label, colour]. 'indigo' is the template's own colour, kept as it was. */
    public const ACCENTS = [
        'indigo'        => ['Indigo (original)', '#6571ff'],
        'indigo-strong' => ['Indigo, stronger', '#4553ff'],
        'blue'          => ['Blue', '#2563eb'],
        'teal'          => ['eLibrary teal', '#00857a'],
        'green'         => ['Green', '#15803d'],
        'purple'        => ['Purple', '#7e22ce'],
        'crimson'       => ['Crimson', '#be123c'],
        'amber'         => ['Amber', '#b45309'],
        'slate'         => ['Slate', '#475569'],
    ];

    /** The template's status colours, and colour-blind-safe ones (Okabe–Ito: bluish green, vermillion, orange, blue). */
    public const STATUS = [
        'standard'   => ['success' => '#05a34a', 'danger' => '#ff3366', 'warning' => '#fbbc06', 'info' => '#66d1d1'],
        'colorblind' => ['success' => '#009e73', 'danger' => '#d55e00', 'warning' => '#e69f00', 'info' => '#0072b2'],
    ];

    public const TEXT = [100, 115, 130, 150];
    public const MOTION = ['auto', 'reduce'];
    public const FONT = ['standard', 'readable'];

    public const DEFAULTS = ['accent' => 'indigo', 'custom' => null, 'status' => 'standard', 'text' => 100, 'motion' => 'auto', 'font' => 'standard'];

    /** The sidebar's background (sidebar-dark), which the accent shows on as text and icons. */
    public const SIDEBAR = '#0c1427';

    public const MIN_CONTRAST = 4.5;

    /** Only known values; anything else falls back to the default. */
    public static function sanitize(array $in): array
    {
        $out = self::DEFAULTS;
        $accent = (string) ($in['accent'] ?? '');
        $custom = strtolower((string) ($in['custom'] ?? ''));
        if ($accent === 'custom' && preg_match('/^#[0-9a-f]{6}$/', $custom)) {
            $out['accent'] = 'custom';
            $out['custom'] = $custom;
        } elseif (isset(self::ACCENTS[$accent])) {
            $out['accent'] = $accent;
        }
        if (isset(self::STATUS[$in['status'] ?? ''])) $out['status'] = $in['status'];
        if (in_array((int) ($in['text'] ?? 0), self::TEXT, true)) $out['text'] = (int) $in['text'];
        if (in_array($in['motion'] ?? '', self::MOTION, true)) $out['motion'] = $in['motion'];
        if (in_array($in['font'] ?? '', self::FONT, true)) $out['font'] = $in['font'];
        return $out;
    }

    /** The library default (Utilities → Theme). */
    public static function siteDefault(): array
    {
        $saved = json_decode((string) setting('appearance_default', ''), true);
        return is_array($saved) ? self::sanitize($saved) : self::DEFAULTS;
    }

    /** What a staff member sees: their own choice, or the library default. */
    public static function for(?User $user): array
    {
        $own = $user?->appearance;
        return is_array($own) ? self::sanitize($own) : self::siteDefault();
    }

    /** The accent a choice uses, before any adjustment. */
    public static function accentColour(array $prefs): string
    {
        return $prefs['accent'] === 'custom' && $prefs['custom'] ? $prefs['custom'] : self::ACCENTS[$prefs['accent']][1] ?? self::ACCENTS['indigo'][1];
    }

    /** The colour variables for <html style="...">: accent (and its sidebar variant), status colours, text size. */
    public static function vars(array $prefs): array
    {
        return self::accentVars($prefs) + self::statusVars($prefs['status'])
            + ['--t-text' => rtrim(rtrim(number_format($prefs['text'] / 100, 2, '.', ''), '0'), '.')];
    }

    public static function accentVars(array $prefs): array
    {
        $accent = self::accentColour($prefs);
        // The original indigo is kept exactly; every other accent is made readable with white text.
        if ($prefs['accent'] !== 'indigo') $accent = self::forWhiteText($accent);
        $onDark = self::onDark($accent);
        return [
            '--t-primary' => $accent, '--t-primary-rgb' => self::rgbList($accent),
            '--t-primary-on-dark' => $onDark, '--t-primary-on-dark-rgb' => self::rgbList($onDark),
        ];
    }

    public static function statusVars(string $status): array
    {
        $vars = [];
        foreach (self::STATUS[$status] ?? self::STATUS['standard'] as $name => $colour) {
            if ($status !== 'standard') $colour = self::forWhiteText($colour);
            $vars["--t-$name"] = $colour;
            $vars["--t-$name-rgb"] = self::rgbList($colour);
            $vars["--t-$name-on-dark"] = self::onDark($colour);   // as text in the dark sidebar (badges)
        }
        return $vars;
    }

    /** For the Appearance form's live preview: every preset's and palette's variables, and their contrast. */
    public static function previewData(): array
    {
        $accents = [];
        foreach (self::ACCENTS as $key => [$label]) {
            $vars = self::accentVars(self::sanitize(['accent' => $key]));
            $accents[$key] = ['label' => $label, 'vars' => $vars, 'contrast' => round(self::contrast($vars['--t-primary'], '#ffffff'), 2)];
        }
        return [
            'accents' => $accents,
            'status'  => array_map(fn ($s) => self::statusVars($s), array_combine(array_keys(self::STATUS), array_keys(self::STATUS))),
            'sidebar' => self::SIDEBAR,
            'min'     => self::MIN_CONTRAST,
        ];
    }

    /** vars() as an inline style. */
    public static function style(array $prefs): string
    {
        $vars = self::vars($prefs);
        return implode('; ', array_map(fn ($k, $v) => "$k: $v", array_keys($vars), $vars));
    }

    /** "Indigo (original) · text 115%", for the Utilities card. */
    public static function describe(array $prefs): string
    {
        $accent = $prefs['accent'] === 'custom' ? 'Custom ' . $prefs['custom'] : self::ACCENTS[$prefs['accent']][0];
        $extra = array_filter([
            $prefs['status'] === 'colorblind' ? 'colour-blind-safe statuses' : null,
            $prefs['text'] !== 100 ? 'text ' . $prefs['text'] . '%' : null,
            $prefs['motion'] === 'reduce' ? 'reduced motion' : null,
            $prefs['font'] === 'readable' ? 'easier-to-read font' : null,
        ]);
        return implode(' · ', array_merge([$accent], $extra));
    }

    // ---- colour maths (WCAG 2.2 relative luminance and contrast) ----

    public static function contrast(string $a, string $b): float
    {
        [$la, $lb] = [self::luminance($a), self::luminance($b)];
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** Darken (same hue) until white text on it, and it as text on white, reach 4.5:1. */
    public static function forWhiteText(string $hex): string
    {
        [$h, $s, $l] = self::hsl($hex);
        while (self::contrast(self::fromHsl($h, $s, $l), '#ffffff') < self::MIN_CONTRAST && $l > 0) $l = max(0, $l - 0.005);
        return self::fromHsl($h, $s, $l);
    }

    /** Lighten (same hue) until it reaches 4.5:1 as text on the dark sidebar. */
    public static function onDark(string $hex): string
    {
        [$h, $s, $l] = self::hsl($hex);
        while (self::contrast(self::fromHsl($h, $s, $l), self::SIDEBAR) < self::MIN_CONTRAST && $l < 1) $l = min(1, $l + 0.005);
        return self::fromHsl($h, $s, $l);
    }

    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function rgbList(string $hex): string
    {
        return implode(', ', self::rgb($hex));
    }

    private static function luminance(string $hex): float
    {
        $c = array_map(function ($v) {
            $v /= 255;
            return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));
        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    private static function hsl(string $hex): array
    {
        [$r, $g, $b] = array_map(fn ($v) => $v / 255, self::rgb($hex));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        if ($max === $min) return [0.0, 0.0, $l];
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        $h = match ($max) {
            $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };
        return [$h / 6, $s, $l];
    }

    private static function fromHsl(float $h, float $s, float $l): string
    {
        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $hue = function ($t) use ($p, $q) {
                if ($t < 0) $t += 1;
                if ($t > 1) $t -= 1;
                if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
                if ($t < 1 / 2) return $q;
                if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
                return $p;
            };
            [$r, $g, $b] = [$hue($h + 1 / 3), $hue($h), $hue($h - 1 / 3)];
        }
        return sprintf('#%02x%02x%02x', round($r * 255), round($g * 255), round($b * 255));
    }
}
