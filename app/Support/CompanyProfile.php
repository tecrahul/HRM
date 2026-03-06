<?php

namespace App\Support;

use App\Models\CompanySetting;
use Illuminate\Support\Arr;

class CompanyProfile
{
    private const DEFAULTS = [
        'company_name' => null,
        'company_logo_path' => null,
        'legal_entity_name' => null,
        'legal_entity_type' => null,
        'registration_number' => null,
        'incorporation_country' => 'US',
        'brand_tagline' => null,
        'brand_primary_color' => '#7C3AED',
        'brand_secondary_color' => '#5EEAD4',
        'brand_font_family' => 'manrope',
        'timezone' => 'UTC',
        'locale' => 'en_US',
        'default_country' => 'US',
        'date_format' => 'M j, Y',
        'time_format' => 'h:i A',
        'branch_directory' => [],
    ];

    private const FONT_STACKS = [
        // Sans-Serif - Modern & Clean
        'inter' => [
            'label' => 'Inter',
            'stack' => '"Inter", ui-sans-serif, system-ui, sans-serif',
        ],
        'manrope' => [
            'label' => 'Manrope',
            'stack' => '"Manrope", ui-sans-serif, system-ui, sans-serif',
        ],
        'plus-jakarta' => [
            'label' => 'Plus Jakarta Sans',
            'stack' => '"Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif',
        ],
        'dm-sans' => [
            'label' => 'DM Sans',
            'stack' => '"DM Sans", ui-sans-serif, system-ui, sans-serif',
        ],
        'outfit' => [
            'label' => 'Outfit',
            'stack' => '"Outfit", ui-sans-serif, system-ui, sans-serif',
        ],
        'nunito-sans' => [
            'label' => 'Nunito Sans',
            'stack' => '"Nunito Sans", ui-sans-serif, system-ui, sans-serif',
        ],
        'source-sans' => [
            'label' => 'Source Sans 3',
            'stack' => '"Source Sans 3", ui-sans-serif, system-ui, sans-serif',
        ],
        'open-sans' => [
            'label' => 'Open Sans',
            'stack' => '"Open Sans", ui-sans-serif, system-ui, sans-serif',
        ],
        'lato' => [
            'label' => 'Lato',
            'stack' => '"Lato", ui-sans-serif, system-ui, sans-serif',
        ],
        'poppins' => [
            'label' => 'Poppins',
            'stack' => '"Poppins", ui-sans-serif, system-ui, sans-serif',
        ],
        'roboto' => [
            'label' => 'Roboto',
            'stack' => '"Roboto", ui-sans-serif, system-ui, sans-serif',
        ],
        'montserrat' => [
            'label' => 'Montserrat',
            'stack' => '"Montserrat", ui-sans-serif, system-ui, sans-serif',
        ],
        'work-sans' => [
            'label' => 'Work Sans',
            'stack' => '"Work Sans", ui-sans-serif, system-ui, sans-serif',
        ],
        'rubik' => [
            'label' => 'Rubik',
            'stack' => '"Rubik", ui-sans-serif, system-ui, sans-serif',
        ],
        'figtree' => [
            'label' => 'Figtree',
            'stack' => '"Figtree", ui-sans-serif, system-ui, sans-serif',
        ],
        // Geometric Sans
        'space-grotesk' => [
            'label' => 'Space Grotesk',
            'stack' => '"Space Grotesk", ui-sans-serif, system-ui, sans-serif',
        ],
        'albert-sans' => [
            'label' => 'Albert Sans',
            'stack' => '"Albert Sans", ui-sans-serif, system-ui, sans-serif',
        ],
        'sora' => [
            'label' => 'Sora',
            'stack' => '"Sora", ui-sans-serif, system-ui, sans-serif',
        ],
        // System Fonts
        'system-ui' => [
            'label' => 'System Default',
            'stack' => 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        ],
        'sf-pro' => [
            'label' => 'SF Pro (Apple)',
            'stack' => '-apple-system, BlinkMacSystemFont, "SF Pro Display", ui-sans-serif, sans-serif',
        ],
        'segoe-ui' => [
            'label' => 'Segoe UI (Windows)',
            'stack' => '"Segoe UI", "Segoe UI Web", ui-sans-serif, system-ui, sans-serif',
        ],
        // Serif - Professional
        'merriweather' => [
            'label' => 'Merriweather',
            'stack' => '"Merriweather", Georgia, "Times New Roman", serif',
        ],
        'playfair' => [
            'label' => 'Playfair Display',
            'stack' => '"Playfair Display", Georgia, "Times New Roman", serif',
        ],
        'lora' => [
            'label' => 'Lora',
            'stack' => '"Lora", Georgia, "Times New Roman", serif',
        ],
        'crimson-pro' => [
            'label' => 'Crimson Pro',
            'stack' => '"Crimson Pro", Georgia, "Times New Roman", serif',
        ],
        // Monospace - Technical
        'plex-mono' => [
            'label' => 'IBM Plex Mono',
            'stack' => '"IBM Plex Mono", "SFMono-Regular", Menlo, monospace',
        ],
        'jetbrains-mono' => [
            'label' => 'JetBrains Mono',
            'stack' => '"JetBrains Mono", "SFMono-Regular", Menlo, monospace',
        ],
        'fira-code' => [
            'label' => 'Fira Code',
            'stack' => '"Fira Code", "SFMono-Regular", Menlo, monospace',
        ],
    ];

    private static ?array $cache = null;

    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $record = CompanySetting::query()->first();
        $data = array_merge(self::DEFAULTS, $record?->toArray() ?? []);
        $data['company_name'] ??= config('app.name');
        $data['timezone'] ??= config('app.timezone');
        if (! is_array($data['branch_directory'])) {
            $data['branch_directory'] = [];
        }

        return self::$cache = $data;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    public static function accentColor(): string
    {
        return self::normalizeColor(
            self::get()['brand_primary_color'] ?? self::DEFAULTS['brand_primary_color'],
            self::DEFAULTS['brand_primary_color']
        );
    }

    public static function secondaryColor(): string
    {
        return self::normalizeColor(
            self::get()['brand_secondary_color'] ?? self::DEFAULTS['brand_secondary_color'],
            self::DEFAULTS['brand_secondary_color']
        );
    }

    public static function accentSoftColor(float $alpha = 0.16): string
    {
        return self::softColor(self::accentColor(), $alpha);
    }

    public static function secondaryAccentSoftColor(float $alpha = 0.18): string
    {
        return self::softColor(self::secondaryColor(), $alpha);
    }

    public static function accentBorderColor(float $alpha = 0.36): string
    {
        return self::softColor(self::accentColor(), $alpha);
    }

    public static function softColor(string $hexColor, float $alpha): string
    {
        $components = self::hexToRgbComponents($hexColor, $hexColor);

        return sprintf('rgba(%d, %d, %d, %.2f)', $components['r'], $components['g'], $components['b'], max(0, min(1, $alpha)));
    }

    public static function fontStacks(): array
    {
        return self::FONT_STACKS;
    }

    public static function fontStack(?string $key = null): string
    {
        $resolvedKey = $key ?? (self::get()['brand_font_family'] ?? 'manrope');

        return Arr::get(self::FONT_STACKS, "{$resolvedKey}.stack", self::FONT_STACKS['manrope']['stack']);
    }

    public static function fontLabel(?string $key = null): string
    {
        $resolvedKey = $key ?? (self::get()['brand_font_family'] ?? 'manrope');

        return Arr::get(self::FONT_STACKS, "{$resolvedKey}.label", self::FONT_STACKS['manrope']['label']);
    }

    private static function normalizeColor(?string $color, string $fallback): string
    {
        if (! is_string($color)) {
            return $fallback;
        }

        $normalized = strtoupper(ltrim($color, '#'));
        if (strlen($normalized) !== 6 || ! ctype_xdigit($normalized)) {
            return $fallback;
        }

        return '#' . $normalized;
    }

    /**
     * @return array{r:int,g:int,b:int}
     */
    private static function hexToRgbComponents(string $hexColor, ?string $fallback = null): array
    {
        $normalized = strtoupper(ltrim($hexColor, '#'));
        if (strlen($normalized) !== 6 || ! ctype_xdigit($normalized)) {
            $fallbackColor = $fallback ?? self::DEFAULTS['brand_primary_color'];
            $normalized = strtoupper(ltrim($fallbackColor, '#'));
        }

        return [
            'r' => hexdec(substr($normalized, 0, 2)),
            'g' => hexdec(substr($normalized, 2, 2)),
            'b' => hexdec(substr($normalized, 4, 2)),
        ];
    }
}
