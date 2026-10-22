<?php

namespace App\Services;

use App\Models\User;

/**
 * How the admin looks for one staff member: colour theme, status colours, background (light, dark or
 * following the device), sidebar, contrast, text size, motion and font.
 *
 * Each person may choose their own (users.appearance, set under Appearance in the profile menu);
 * everyone else follows the library default (settings 'appearance_default', Utilities → Theme). The
 * admin layout turns it into data-* attributes on <html>, the light and/or dark stylesheet
 * (public/css/admin-theme.css and admin-theme-dark.css, generated from NobleUI's by
 * scripts/admin-theme.mjs) and a <style> block of variables (css()):
 *   --t-*   the accent, status colours, font and text size (stage 1);
 *   --ui-*  the roles a background or high contrast sets: page, surface, surface-2, border, text,
 *           text-2, muted, muted-2, and status ink / soft / line. Unset for the standard light look, so
 *           every value falls back to the template's own.
 *
 * Colours are checked against WCAG 2.2: text 4.5:1 (7:1 with high contrast) on the backgrounds it sits
 * on; accents, status colours and custom backgrounds are adjusted until they reach it.
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

    /** key => [label, scheme, page colour]. Standard and Dark are the template's own (NobleUI's light and dark). */
    public const BACKGROUNDS = [
        'standard' => ['Standard', 'light', null],
        'white'    => ['White', 'light', '#ffffff'],
        'cream'    => ['Warm cream', 'light', '#f6efdf'],
        'cool'     => ['Cool grey', 'light', '#e7ecf3'],
        'dark'     => ['Dark', 'dark', null],
        'dim'      => ['Dim', 'dark', '#202329'],
        'black'    => ['Black', 'dark', '#000000'],
        'auto'     => ['Follow my device', 'auto', null],
    ];

    public const TEXT = [100, 115, 130, 150];
    public const MOTION = ['auto', 'reduce'];
    public const FONT = ['standard', 'readable'];
    public const SIDEBARS = ['dark', 'light'];
    public const CONTRAST = ['standard', 'high'];

    public const DEFAULTS = [
        'accent' => 'indigo', 'custom' => null, 'status' => 'standard', 'background' => 'standard', 'background_custom' => null,
        'sidebar' => 'dark', 'contrast' => 'standard', 'text' => 100, 'motion' => 'auto', 'font' => 'standard',
    ];

    /** The dark sidebar's background (and NobleUI's dark surface). */
    public const SIDEBAR = '#0c1427';

    public const MIN_CONTRAST = 4.5;
    public const HIGH_CONTRAST = 7.0;

    /** NobleUI's dark look, as roles (its own colours; muted text a little lighter so it reads on raised panels too). */
    private const DARK = ['page' => '#070d19', 'surface' => '#0c1427', 'surface-2' => '#121e3b', 'border' => '#172340', 'text' => '#d0d6e1'];
    private const HIGH_LIGHT = ['page' => '#ffffff', 'surface' => '#ffffff', 'surface-2' => '#f0f0f0', 'border' => '#6f6f6f', 'text' => '#000000'];
    private const HIGH_DARK = ['page' => '#000000', 'surface' => '#000000', 'surface-2' => '#141414', 'border' => '#8c8c8c', 'text' => '#ffffff'];
    private const NAVY = '#33509c';   // our filter chips' colour

    /** Only known values; anything else falls back to the default. */
    public static function sanitize(array $in): array
    {
        $out = self::DEFAULTS;
        $hex = fn ($v) => preg_match('/^#[0-9a-f]{6}$/', $v = strtolower((string) $v)) ? $v : null;

        $accent = (string) ($in['accent'] ?? '');
        if ($accent === 'custom' && ($c = $hex($in['custom'] ?? null))) {
            $out['accent'] = 'custom';
            $out['custom'] = $c;
        } elseif (isset(self::ACCENTS[$accent])) {
            $out['accent'] = $accent;
        }
        $background = (string) ($in['background'] ?? '');
        if ($background === 'custom' && ($c = $hex($in['background_custom'] ?? null))) {
            $out['background'] = 'custom';
            $out['background_custom'] = $c;
        } elseif (isset(self::BACKGROUNDS[$background])) {
            $out['background'] = $background;
        }
        if (isset(self::STATUS[$in['status'] ?? ''])) $out['status'] = $in['status'];
        if (in_array((int) ($in['text'] ?? 0), self::TEXT, true)) $out['text'] = (int) $in['text'];
        if (in_array($in['motion'] ?? '', self::MOTION, true)) $out['motion'] = $in['motion'];
        if (in_array($in['font'] ?? '', self::FONT, true)) $out['font'] = $in['font'];
        if (in_array($in['sidebar'] ?? '', self::SIDEBARS, true)) $out['sidebar'] = $in['sidebar'];
        if (in_array($in['contrast'] ?? '', self::CONTRAST, true)) $out['contrast'] = $in['contrast'];
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

    // ---- the page: scheme, stylesheets, body class, variables ----

    /** light, dark, or auto (the device's setting). A custom background is light or dark by its own lightness. */
    public static function mode(array $p): string
    {
        if ($p['background'] === 'custom') return self::isLight(self::customPage($p)) ? 'light' : 'dark';
        return self::BACKGROUNDS[$p['background']][1];
    }

    /** The media attribute of the light or dark stylesheet's <link>. */
    public static function media(array $p, string $scheme): string
    {
        $mode = self::mode($p);
        if ($mode === 'auto') return "(prefers-color-scheme: $scheme)";
        return $mode === $scheme ? 'all' : 'not all';
    }

    /** <body> class: the dark sidebar (the template's light sidebar has none; the dark stylesheet's sidebar is always dark). */
    public static function bodyClass(array $p): string
    {
        return $p['sidebar'] === 'dark' || self::mode($p) === 'dark' ? 'sidebar-dark' : '';
    }

    /** The data-* attributes on <html>. */
    public static function attributes(array $p): array
    {
        return ['data-mode' => self::mode($p), 'data-contrast' => $p['contrast'], 'data-sidebar' => $p['sidebar'],
                'data-text' => $p['text'], 'data-motion' => $p['motion'], 'data-font' => $p['font'], 'data-status' => $p['status']];
    }

    /** The <style> block: the variables for the scheme in use (and, following the device, for its dark setting). */
    public static function css(array $p): string
    {
        $block = fn (array $vars) => implode(' ', array_map(fn ($k, $v) => "$k: $v;", array_keys($vars), $vars));
        $mode = self::mode($p);
        $css = 'html:root { ' . $block(self::vars($p, $mode === 'dark' ? 'dark' : 'light')) . ' }';
        if ($mode === 'auto') $css .= "\n@media (prefers-color-scheme: dark) { html:root { " . $block(self::vars($p, 'dark')) . ' } }';
        return $css;
    }

    /** All the variables for one scheme: accent, status colours, text size, and the roles. */
    public static function vars(array $p, ?string $scheme = null): array
    {
        $scheme ??= self::mode($p) === 'dark' ? 'dark' : 'light';
        $roles = self::roles($p, $scheme);
        $high = $p['contrast'] === 'high';
        $min = $high ? self::HIGH_CONTRAST : self::MIN_CONTRAST;

        // The accent: white text on it reads (buttons), and it reads as text on the page; its on-dark variant
        // reads on the dark sidebar, or on the dark scheme's raised panels.
        $accent = self::accentColour($p);
        if ($p['accent'] !== 'indigo' || $high) $accent = self::forWhiteText($accent, $min);
        if ($scheme === 'light' && isset($roles['--ui-page'])) $accent = self::darkenUntil($accent, $roles['--ui-page'], $min);
        $darkBg = $scheme === 'dark' ? ($roles['--ui-surface-2'] ?? self::DARK['surface-2']) : self::SIDEBAR;
        $onDark = self::lightenUntil($accent, $darkBg, $min);
        $vars = ['--t-primary' => $accent, '--t-primary-rgb' => self::rgbList($accent), '--t-primary-on-dark' => $onDark, '--t-primary-on-dark-rgb' => self::rgbList($onDark)];

        foreach (self::statusBase($p) as $name => $colour) {
            $on = self::lightenUntil($colour, $darkBg, $min);
            $vars += ["--t-$name" => $colour, "--t-$name-rgb" => self::rgbList($colour), "--t-$name-on-dark" => $on, "--t-$name-on-dark-rgb" => self::rgbList($on)];
        }
        $vars['--t-text'] = rtrim(rtrim(number_format($p['text'] / 100, 2, '.', ''), '0'), '.');
        return $vars + $roles;
    }

    /** A palette's status colours with their on-dark variants (as the light scheme sets them). */
    public static function statusVars(string $status): array
    {
        $vars = self::vars(self::sanitize(['status' => $status]), 'light');
        return array_filter($vars, fn ($k) => preg_match('/^--t-(success|danger|warning|info)/', $k), ARRAY_FILTER_USE_KEY);
    }

    /**
     * The --ui-* roles for a scheme. None for the standard light look (the template's own colours);
     * otherwise page, panels, borders and text worked out from the background, and status inks on soft
     * backgrounds, all reading at 4.5:1 (7:1 with high contrast).
     */
    public static function roles(array $p, string $scheme): array
    {
        $high = $p['contrast'] === 'high';
        $base = null;
        if ($scheme === 'light') {
            if ($high) $base = self::HIGH_LIGHT;
            elseif ($page = self::pageColour($p, 'light')) $base = self::deriveLight($page);
        } else {
            if ($high) $base = self::HIGH_DARK;
            elseif ($page = self::pageColour($p, 'dark')) $base = self::deriveDark($page);
            else $base = self::DARK;
        }
        if (! $base && $p['status'] === 'standard') return [];   // the template's own look

        $base ??= ['page' => '#f9fafb', 'surface' => '#ffffff', 'surface-2' => '#e9ecef', 'border' => '#e9ecef', 'text' => '#000000'];
        $roles = $scheme === 'light' && ! $high && ! self::pageColour($p, 'light') ? [] : self::neutralRoles($base, $high);

        // Status ink on its soft background, the soft background and a line, for our chips and notes.
        $surface = $base['surface'];
        $min = $high ? self::HIGH_CONTRAST : self::MIN_CONTRAST;
        $dark = $scheme === 'dark';
        foreach (self::statusBase($p) + ['navy' => self::NAVY] as $name => $colour) {
            $soft = self::mix($colour, $surface, $dark ? 0.80 : 0.88);
            $ink = $dark ? self::lightenUntil($colour, $soft, $min) : self::darkenUntil($colour, $soft, $min);
            if ($name === 'navy') { $roles += ['--ui-navy' => $ink, '--ui-navy-soft' => $soft, '--ui-navy-solid' => self::forWhiteText($colour, $min)]; continue; }
            $roles += ["--ui-$name-ink" => $ink, "--ui-$name-soft" => $soft, "--ui-$name-line" => self::mix($colour, $surface, $dark ? 0.55 : 0.65)];
        }
        if ($dark) $roles['--ui-accent-text'] = 'var(--t-primary-on-dark)';
        return $roles;
    }

    /** Page, panels, borders and the three text strengths from a base set. */
    private static function neutralRoles(array $b, bool $high): array
    {
        $worst = self::contrast($b['text'], $b['surface-2']) < self::contrast($b['text'], $b['page']) ? $b['surface-2'] : $b['page'];
        $text2 = self::toward($b['text'], $worst, $high ? 12.0 : 7.0);
        $muted = self::toward($b['text'], $worst, $high ? self::HIGH_CONTRAST : self::MIN_CONTRAST + 0.1);
        return ['--ui-page' => $b['page'], '--ui-surface' => $b['surface'], '--ui-surface-2' => $b['surface-2'], '--ui-border' => $b['border'],
                '--ui-text' => $b['text'], '--ui-text-2' => $text2, '--ui-muted' => $muted, '--ui-muted-2' => $muted];
    }

    /** A light background's set: lighter panels, slightly darker raised panels and borders, near-black text. */
    private static function deriveLight(string $page): array
    {
        $text = self::darkenUntil('#1d2433', $page, 12.0);
        return ['page' => $page, 'surface' => self::mix($page, '#ffffff', 0.65), 'surface-2' => self::mix($page, '#000000', 0.05),
                'border' => self::mix($page, '#000000', 0.12), 'text' => $text];
    }

    /** A dark background's set: slightly lighter panels and borders, light text. */
    private static function deriveDark(string $page): array
    {
        $text = self::lightenUntil('#d8dde6', $page, 11.0);
        return ['page' => $page, 'surface' => self::mix($page, '#ffffff', 0.045), 'surface-2' => self::mix($page, '#ffffff', 0.09),
                'border' => self::mix($page, '#ffffff', 0.17), 'text' => $text];
    }

    /** The chosen background's page colour for a scheme, or null for the template's own (Standard, Dark, Follow my device). */
    public static function pageColour(array $p, string $scheme): ?string
    {
        if ($p['background'] === 'custom') {
            $page = self::customPage($p);
            return (self::isLight($page) ? 'light' : 'dark') === $scheme ? $page : null;
        }
        [, $bgScheme, $page] = self::BACKGROUNDS[$p['background']];
        return $bgScheme === $scheme ? $page : null;
    }

    /** A custom background, made light or dark enough for text on it to read at 7:1. */
    public static function customPage(array $p): string
    {
        $c = $p['background_custom'] ?: '#ffffff';
        return self::isLight($c) ? self::lightenUntil($c, '#1d2433', 7.0) : self::darkenUntil($c, '#d8dde6', 7.0);
    }

    private static function isLight(string $hex): bool
    {
        return self::contrast($hex, '#000000') >= self::contrast($hex, '#ffffff');
    }

    /** The status colours: the palette, darkened for white text when not the template's own. */
    private static function statusBase(array $p): array
    {
        $high = $p['contrast'] === 'high';
        $out = [];
        foreach (self::STATUS[$p['status']] as $name => $colour) {
            $out[$name] = $p['status'] === 'standard' && ! $high ? $colour : self::forWhiteText($colour, $high ? self::HIGH_CONTRAST : self::MIN_CONTRAST);
        }
        return $out;
    }

    /** The accent a choice uses, before any adjustment. */
    public static function accentColour(array $p): string
    {
        return $p['accent'] === 'custom' && $p['custom'] ? $p['custom'] : self::ACCENTS[$p['accent']][1] ?? self::ACCENTS['indigo'][1];
    }

    // ---- the Appearance form ----

    /** The live preview: what the layout would put on the page for this choice (AppearanceController::preview). */
    public static function preview(array $p): array
    {
        $notes = [];
        $vars = self::vars($p);
        $accent = self::accentColour($p);
        if ($vars['--t-primary'] !== $accent && ($p['accent'] !== 'indigo' || $p['contrast'] === 'high')) {
            $notes[] = 'The accent is adjusted to ' . $vars['--t-primary'] . ' so text on it stays readable.';
        }
        if ($p['background'] === 'custom' && self::customPage($p) !== $p['background_custom']) {
            $notes[] = 'The background is adjusted to ' . self::customPage($p) . ' so text on it stays readable.';
        }
        return ['css' => self::css($p), 'attributes' => self::attributes($p), 'bodyClass' => self::bodyClass($p),
                'media' => ['light' => self::media($p, 'light'), 'dark' => self::media($p, 'dark')], 'notes' => $notes];
    }

    /** For the form: each accent's colour and contrast, each background's page / panel / text colours. */
    public static function options(): array
    {
        $accents = [];
        foreach (self::ACCENTS as $key => [$label, $colour]) {
            $c = self::vars(self::sanitize(['accent' => $key]), 'light')['--t-primary'];
            $accents[$key] = ['label' => $label, 'colour' => $c, 'contrast' => round(self::contrast($c, '#ffffff'), 2)];
        }
        $backgrounds = [];
        foreach (self::BACKGROUNDS as $key => [$label, $scheme]) {
            $p = self::sanitize(['background' => $key]);
            $light = self::roles($p, 'light') + ['--ui-page' => '#f9fafb', '--ui-surface' => '#ffffff', '--ui-text' => '#000000'];
            $dark = self::roles($p, 'dark');
            $r = $scheme === 'dark' ? $dark : $light;
            $backgrounds[$key] = ['label' => $label, 'scheme' => $scheme, 'page' => $r['--ui-page'], 'surface' => $r['--ui-surface'], 'text' => $r['--ui-text'],
                'darkPage' => $dark['--ui-page'], 'darkSurface' => $dark['--ui-surface']];
        }
        return ['accents' => $accents, 'backgrounds' => $backgrounds, 'min' => self::MIN_CONTRAST];
    }

    /** "Indigo (original) · Warm cream · text 115%", for the Utilities card. */
    public static function describe(array $p): string
    {
        $accent = $p['accent'] === 'custom' ? 'Custom ' . $p['custom'] : self::ACCENTS[$p['accent']][0];
        $background = $p['background'] === 'custom' ? 'custom background ' . $p['background_custom'] : self::BACKGROUNDS[$p['background']][0];
        $extra = array_filter([
            $p['background'] !== 'standard' ? $background : null,
            $p['sidebar'] === 'light' ? 'light sidebar' : null,
            $p['contrast'] === 'high' ? 'high contrast' : null,
            $p['status'] === 'colorblind' ? 'colour-blind-safe statuses' : null,
            $p['text'] !== 100 ? 'text ' . $p['text'] . '%' : null,
            $p['motion'] === 'reduce' ? 'reduced motion' : null,
            $p['font'] === 'readable' ? 'easier-to-read font' : null,
        ]);
        return implode(' · ', array_merge([$accent], $extra));
    }

    // ---- colour maths (WCAG 2.2 relative luminance and contrast) ----

    public static function contrast(string $a, string $b): float
    {
        [$la, $lb] = [self::luminance($a), self::luminance($b)];
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** Darken (same hue) until white text on it, and it as text on white, reach $min. */
    public static function forWhiteText(string $hex, float $min = self::MIN_CONTRAST): string
    {
        return self::darkenUntil($hex, '#ffffff', $min);
    }

    /** Darken (same hue) until it reaches $min against $bg. */
    public static function darkenUntil(string $hex, string $bg, float $min): string
    {
        [$h, $s, $l] = self::hsl($hex);
        while (self::contrast(self::fromHsl($h, $s, $l), $bg) < $min && $l > 0) $l = max(0, $l - 0.005);
        return self::fromHsl($h, $s, $l);
    }

    /** Lighten (same hue) until it reaches $min against $bg. */
    public static function lightenUntil(string $hex, string $bg, float $min): string
    {
        [$h, $s, $l] = self::hsl($hex);
        while (self::contrast(self::fromHsl($h, $s, $l), $bg) < $min && $l < 1) $l = min(1, $l + 0.005);
        return self::fromHsl($h, $s, $l);
    }

    /** The colour between $fg and $bg as close to $bg as possible while keeping $min against $bg. */
    private static function toward(string $fg, string $bg, float $min): string
    {
        $best = $fg;
        for ($t = 0.02; $t <= 0.98; $t += 0.02) {
            $c = self::mix($fg, $bg, $t);
            if (self::contrast($c, $bg) < $min) break;
            $best = $c;
        }
        return $best;
    }

    /** $a mixed with $b: $w of $b. */
    public static function mix(string $a, string $b, float $w): string
    {
        [$x, $y] = [self::rgb($a), self::rgb($b)];
        return sprintf('#%02x%02x%02x', ...array_map(fn ($i) => round($x[$i] * (1 - $w) + $y[$i] * $w), [0, 1, 2]));
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
