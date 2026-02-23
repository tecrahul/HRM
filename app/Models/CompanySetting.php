<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CompanySetting extends Model
{
    public const DEFAULT_SIGNUP_ENABLED = false;
    public const DEFAULT_PASSWORD_RESET_ENABLED = true;
    public const DEFAULT_TWO_FACTOR_ENABLED = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'branch_directory' => 'array',
            'financial_year_start_month' => 'integer',
            'financial_year_start_day' => 'integer',
            'financial_year_end_month' => 'integer',
            'financial_year_end_day' => 'integer',
            'signup_enabled' => 'boolean',
            'password_reset_enabled' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public static function signupEnabled(): bool
    {
        if (! Schema::hasTable((new self())->getTable())) {
            return self::DEFAULT_SIGNUP_ENABLED;
        }

        try {
            $value = self::query()->value('signup_enabled');
        } catch (Throwable) {
            return self::DEFAULT_SIGNUP_ENABLED;
        }

        return $value === null ? self::DEFAULT_SIGNUP_ENABLED : (bool) $value;
    }

    public static function passwordResetEnabled(): bool
    {
        if (! Schema::hasTable((new self())->getTable())) {
            return self::DEFAULT_PASSWORD_RESET_ENABLED;
        }

        try {
            $value = self::query()->value('password_reset_enabled');
        } catch (Throwable) {
            return self::DEFAULT_PASSWORD_RESET_ENABLED;
        }

        return $value === null ? self::DEFAULT_PASSWORD_RESET_ENABLED : (bool) $value;
    }

    public static function twoFactorEnabled(): bool
    {
        if (! Schema::hasTable((new self())->getTable())) {
            return self::DEFAULT_TWO_FACTOR_ENABLED;
        }

        try {
            $value = self::query()->value('two_factor_enabled');
        } catch (Throwable) {
            return self::DEFAULT_TWO_FACTOR_ENABLED;
        }

        return $value === null ? self::DEFAULT_TWO_FACTOR_ENABLED : (bool) $value;
    }
}
