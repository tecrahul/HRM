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
        'company_code',
        'company_email',
        'company_phone',
        'company_website',
        'tax_id',
        'timezone',
        'currency',
        'financial_year_start_month',
        'company_address',
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
            'financial_year_start_month' => 'integer',
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
