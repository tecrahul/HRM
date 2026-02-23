<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Support\ActivityLogger;
use App\Support\CompanyProfile;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SettingsController extends Controller
{
    /**
     * @return list<string>
     */
    private function fields(): array
    {
        return [
            'company_name',
            'company_logo_path',
            'brand_primary_color',
            'brand_secondary_color',
            'brand_font_family',
            'brand_tagline',
            'company_code',
            'company_email',
            'company_phone',
            'company_website',
            'tax_id',
            'legal_entity_name',
            'legal_entity_type',
            'registration_number',
            'incorporation_country',
            'timezone',
            'locale',
            'default_country',
            'date_format',
            'time_format',
            'currency',
            'financial_year_start_month',
            'financial_year_start_day',
            'financial_year_end_month',
            'financial_year_end_day',
            'company_address',
            'branch_directory',
            'signup_enabled',
            'password_reset_enabled',
            'two_factor_enabled',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'company_name' => config('app.name'),
            'company_logo_path' => null,
            'brand_primary_color' => '#7C3AED',
            'brand_secondary_color' => '#5EEAD4',
            'brand_font_family' => 'manrope',
            'brand_tagline' => null,
            'company_code' => null,
            'company_email' => config('mail.from.address'),
            'company_phone' => null,
            'company_website' => config('app.url'),
            'tax_id' => null,
            'legal_entity_name' => null,
            'legal_entity_type' => null,
            'registration_number' => null,
            'incorporation_country' => 'US',
            'timezone' => (string) config('app.timezone', 'UTC'),
            'locale' => 'en_US',
            'default_country' => 'US',
            'date_format' => 'M j, Y',
            'time_format' => 'h:i A',
            'currency' => 'USD',
            'financial_year_start_month' => 4,
            'financial_year_start_day' => 1,
            'financial_year_end_month' => null,
            'financial_year_end_day' => null,
            'company_address' => null,
            'branch_directory' => [],
            'signup_enabled' => CompanySetting::DEFAULT_SIGNUP_ENABLED,
            'password_reset_enabled' => CompanySetting::DEFAULT_PASSWORD_RESET_ENABLED,
            'two_factor_enabled' => CompanySetting::DEFAULT_TWO_FACTOR_ENABLED,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function timezoneOptions(): array
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'America / New York',
            'America/Chicago' => 'America / Chicago',
            'America/Los_Angeles' => 'America / Los Angeles',
            'Europe/London' => 'Europe / London',
            'Europe/Berlin' => 'Europe / Berlin',
            'Europe/Paris' => 'Europe / Paris',
            'Asia/Dubai' => 'Asia / Dubai',
            'Asia/Kolkata' => 'Asia / Kolkata',
            'Asia/Singapore' => 'Asia / Singapore',
            'Australia/Sydney' => 'Australia / Sydney',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function localeOptions(): array
    {
        return [
            'en_US' => 'English (United States)',
            'en_GB' => 'English (United Kingdom)',
            'fr_FR' => 'Français (France)',
            'es_MX' => 'Español (México)',
            'pt_BR' => 'Português (Brasil)',
            'hi_IN' => 'हिन्दी (भारत)',
            'de_DE' => 'Deutsch (Deutschland)',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function dateFormatOptions(): array
    {
        $sample = Carbon::create(2026, 1, 4, 9, 30);
        $formats = ['M j, Y', 'd/m/Y', 'Y-m-d', 'j M Y', 'm.d.Y'];

        return collect($formats)
            ->mapWithKeys(fn (string $format): array => [$format => $sample->format($format)])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function timeFormatOptions(): array
    {
        $sample = Carbon::createFromTime(20, 30);
        $formats = ['h:i A', 'H:i', 'g:i a'];

        return collect($formats)
            ->mapWithKeys(fn (string $format): array => [$format => $sample->format($format)])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function countryOptions(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'SG' => 'Singapore',
            'IN' => 'India',
            'DE' => 'Germany',
            'FR' => 'France',
            'BR' => 'Brazil',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function legalEntityTypes(): array
    {
        return [
            'llc' => 'Limited Liability Company (LLC)',
            'c_corp' => 'C-Corporation',
            's_corp' => 'S-Corporation',
            'plc' => 'Public Limited Company',
            'partnership' => 'Partnership / LLP',
            'nonprofit' => 'Nonprofit / NGO',
            'government' => 'Government / Public Sector',
            'sole_prop' => 'Sole Proprietorship',
            'other' => 'Other / Custom',
        ];
    }

    /**
     * @return array<string, array{label:string,stack:string}>
     */
    private function brandFontOptions(): array
    {
        return CompanyProfile::fontStacks();
    }

    private function branchDirectoryLimit(): int
    {
        return 8;
    }

    public function index(Request $request): View
    {
        $record = CompanySetting::query()->first();
        $companySettings = array_merge($this->defaults(), $record?->only($this->fields()) ?? []);
        $settingsSection = $this->resolveSettingsSection((string) $request->query('section', 'overview'));

        if (! is_array($companySettings['branch_directory'])) {
            $companySettings['branch_directory'] = [];
        }

        if (
            empty($companySettings['financial_year_end_month'])
            || empty($companySettings['financial_year_end_day'])
        ) {
            $derivedEnd = Carbon::create(
                2024,
                (int) ($companySettings['financial_year_start_month'] ?? 4),
                (int) ($companySettings['financial_year_start_day'] ?? 1)
            )->addYear()->subDay();

            $companySettings['financial_year_end_month'] = (int) $derivedEnd->format('n');
            $companySettings['financial_year_end_day'] = (int) $derivedEnd->format('j');
        }

        $financialYearMonthOptions = collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [$month => Carbon::create(2024, $month, 1)->format('F')])
            ->all();
        $financialYearDayOptions = range(1, 31);

        return view('settings.index', [
            'companySettings' => $companySettings,
            'financialYearMonthOptions' => $financialYearMonthOptions,
            'financialYearDayOptions' => $financialYearDayOptions,
            'timezoneOptions' => $this->timezoneOptions(),
            'localeOptions' => $this->localeOptions(),
            'dateFormatOptions' => $this->dateFormatOptions(),
            'timeFormatOptions' => $this->timeFormatOptions(),
            'countryOptions' => $this->countryOptions(),
            'legalEntityTypes' => $this->legalEntityTypes(),
            'brandFontOptions' => $this->brandFontOptions(),
            'branchDirectoryLimit' => $this->branchDirectoryLimit(),
            'settingsSection' => $settingsSection,
            'settingsPageHeading' => $this->pageHeadingForSection($settingsSection),
        ]);
    }

    public function updateCompanyDetails(Request $request): RedirectResponse
    {
        $settingsSectionInput = Str::lower((string) $request->input('settings_section', ''));
        $settingsSection = in_array($settingsSectionInput, ['company', 'system'], true)
            ? $settingsSectionInput
            : null;
        $timezoneOptions = array_keys($this->timezoneOptions());
        $localeOptions = array_keys($this->localeOptions());
        $countryOptions = array_keys($this->countryOptions());
        $dateFormatOptions = array_keys($this->dateFormatOptions());
        $timeFormatOptions = array_keys($this->timeFormatOptions());
        $legalEntityTypes = array_keys($this->legalEntityTypes());
        $brandFontOptions = array_keys($this->brandFontOptions());

        $companyRules = [
            'company_name' => ['required', 'string', 'max:255'],
            'legal_entity_name' => ['nullable', 'string', 'max:255'],
            'legal_entity_type' => ['nullable', 'string', Rule::in($legalEntityTypes)],
            'registration_number' => ['nullable', 'string', 'max:160'],
            'incorporation_country' => ['nullable', 'string', 'size:2', Rule::in($countryOptions)],
            'brand_tagline' => ['nullable', 'string', 'max:160'],
            'brand_primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_font_family' => ['required', Rule::in($brandFontOptions)],
            'company_code' => ['nullable', 'string', 'max:100'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:60'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'branch_directory' => ['nullable', 'array', 'max:' . $this->branchDirectoryLimit()],
            'branch_directory.*.label' => ['nullable', 'string', 'max:120'],
            'branch_directory.*.code' => ['nullable', 'string', 'max:20'],
            'branch_directory.*.timezone' => ['nullable', Rule::in($timezoneOptions)],
            'branch_directory.*.phone' => ['nullable', 'string', 'max:60'],
            'branch_directory.*.address' => ['nullable', 'string', 'max:500'],
            'branch_directory.*.is_primary' => ['nullable', 'boolean'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_company_logo' => ['nullable', 'boolean'],
        ];
        $systemRules = [
            'timezone' => ['required', Rule::in($timezoneOptions)],
            'locale' => ['required', Rule::in($localeOptions)],
            'default_country' => ['required', 'string', 'size:2', Rule::in($countryOptions)],
            'date_format' => ['required', Rule::in($dateFormatOptions)],
            'time_format' => ['required', Rule::in($timeFormatOptions)],
            'currency' => ['required', 'string', 'size:3'],
            'financial_year_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'financial_year_start_day' => ['required', 'integer', 'min:1', 'max:31'],
            'financial_year_end_month' => ['required', 'integer', 'min:1', 'max:12'],
            'financial_year_end_day' => ['required', 'integer', 'min:1', 'max:31'],
            'signup_enabled' => ['nullable', 'boolean'],
            'password_reset_enabled' => ['nullable', 'boolean'],
            'two_factor_enabled' => ['nullable', 'boolean'],
        ];

        $validationRules = match ($settingsSection) {
            'company' => $companyRules,
            'system' => $systemRules,
            default => array_merge($companyRules, $systemRules),
        };
        $validated = $request->validate($validationRules);

        if ($settingsSection !== 'company') {
            $validated['signup_enabled'] = $request->boolean('signup_enabled');
            $validated['password_reset_enabled'] = $request->boolean('password_reset_enabled');
            $validated['two_factor_enabled'] = $request->boolean('two_factor_enabled');
        }

        if (array_key_exists('currency', $validated)) {
            $validated['currency'] = strtoupper($validated['currency']);
        }

        if (array_key_exists('incorporation_country', $validated)) {
            $validated['incorporation_country'] = strtoupper((string) ($validated['incorporation_country'] ?? ''));
        }

        if (array_key_exists('default_country', $validated)) {
            $validated['default_country'] = strtoupper($validated['default_country']);
        }

        if (array_key_exists('brand_primary_color', $validated)) {
            $validated['brand_primary_color'] = strtoupper($validated['brand_primary_color']);
        }

        if (array_key_exists('brand_secondary_color', $validated)) {
            $validated['brand_secondary_color'] = strtoupper($validated['brand_secondary_color']);
        }

        if (array_key_exists('locale', $validated)) {
            $validated['locale'] = str_replace('-', '_', $validated['locale']);
        }

        if (isset(
            $validated['financial_year_start_month'],
            $validated['financial_year_start_day'],
            $validated['financial_year_end_month'],
            $validated['financial_year_end_day']
        )) {
            $this->validateFinancialYearBoundaries($validated);
        }

        if ($settingsSection !== 'system') {
            $validated['branch_directory'] = $this->normalizeBranchDirectory($request->input('branch_directory', []));
        }

        unset($validated['company_logo'], $validated['remove_company_logo']);

        $settings = CompanySetting::query()->firstOrNew([]);
        $settings->fill($validated);

        if ($settingsSection !== 'system') {
            if ($request->boolean('remove_company_logo') && filled($settings->company_logo_path)) {
                Storage::disk('public')->delete((string) $settings->company_logo_path);
                $settings->company_logo_path = null;
            }

            if ($request->hasFile('company_logo')) {
                $newLogoPath = $request->file('company_logo')->store('company-logos', 'public');

                if (filled($settings->company_logo_path) && $settings->company_logo_path !== $newLogoPath) {
                    Storage::disk('public')->delete((string) $settings->company_logo_path);
                }

                $settings->company_logo_path = $newLogoPath;
            }
        }

        $settings->save();
        CompanyProfile::flush();

        $isSystemUpdate = $settingsSection === 'system';
        ActivityLogger::log(
            $request->user(),
            $isSystemUpdate ? 'settings.system_updated' : 'settings.company_updated',
            $isSystemUpdate ? 'System settings updated' : 'Company settings updated',
            (string) ($settings->company_name ?: config('app.name')),
            $isSystemUpdate ? '#0891b2' : '#7c3aed',
            $settings
        );

        $redirectParameters = [];
        if (in_array($settingsSection, ['company', 'system'], true)) {
            $redirectParameters['section'] = $settingsSection;
        }

        return redirect()
            ->route('settings.index', $redirectParameters)
            ->with('status', $isSystemUpdate ? 'System settings updated successfully.' : 'Company details updated successfully.');
    }

    public function companyLogo(): BinaryFileResponse|Response
    {
        $logoPath = (string) CompanySetting::query()->value('company_logo_path');

        if ($logoPath === '' || ! str_starts_with($logoPath, 'company-logos/')) {
            return response('', 404);
        }

        if (! Storage::disk('public')->exists($logoPath)) {
            return response('', 404);
        }

        return response()->file(
            Storage::disk('public')->path($logoPath),
            ['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0']
        );
    }

    private function validateFinancialYearBoundaries(array $validated): void
    {
        $startMonth = (int) $validated['financial_year_start_month'];
        $startDay = (int) $validated['financial_year_start_day'];
        $endMonth = (int) $validated['financial_year_end_month'];
        $endDay = (int) $validated['financial_year_end_day'];

        if (! checkdate($startMonth, $startDay, 2025)) {
            throw ValidationException::withMessages([
                'financial_year_start_day' => 'Invalid day for the selected financial year start month.',
            ]);
        }

        if (! checkdate($endMonth, $endDay, 2025)) {
            throw ValidationException::withMessages([
                'financial_year_end_day' => 'Invalid day for the selected financial year end month.',
            ]);
        }

        if ($startMonth === $endMonth && $startDay === $endDay) {
            throw ValidationException::withMessages([
                'financial_year_end_day' => 'Financial year start and end boundaries cannot be identical.',
            ]);
        }
    }

    /**
     * @param array<int, mixed> $rawEntries
     * @return array<int, array<string, mixed>>
     */
    private function normalizeBranchDirectory(array $rawEntries): array
    {
        $timezoneOptions = array_keys($this->timezoneOptions());
        $primaryPinned = false;

        return collect($rawEntries)
            ->filter(static fn ($entry): bool => is_array($entry))
            ->take($this->branchDirectoryLimit())
            ->map(function (array $entry) use (&$primaryPinned, $timezoneOptions): array {
                $timezone = trim((string) ($entry['timezone'] ?? ''));
                $normalizedTimezone = in_array($timezone, $timezoneOptions, true) ? $timezone : null;
                $isPrimary = isset($entry['is_primary']) && (bool) $entry['is_primary'] && ! $primaryPinned;

                if ($isPrimary) {
                    $primaryPinned = true;
                }

                return [
                    'label' => trim((string) ($entry['label'] ?? '')),
                    'code' => strtoupper(trim((string) ($entry['code'] ?? ''))),
                    'address' => trim((string) ($entry['address'] ?? '')),
                    'phone' => trim((string) ($entry['phone'] ?? '')),
                    'timezone' => $normalizedTimezone,
                    'is_primary' => $isPrimary,
                ];
            })
            ->filter(static fn (array $entry): bool => $entry['label'] !== '' || $entry['address'] !== '' || $entry['code'] !== '')
            ->values()
            ->all();
    }

    private function resolveSettingsSection(string $rawSection): string
    {
        $section = Str::lower(trim($rawSection));

        return in_array($section, ['overview', 'company', 'system'], true)
            ? $section
            : 'overview';
    }

    private function pageHeadingForSection(string $section): string
    {
        return match ($section) {
            'company' => 'Company Settings',
            'system' => 'System Settings',
            default => 'Settings Overview',
        };
    }
}
