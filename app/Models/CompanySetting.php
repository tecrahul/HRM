<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CompanySetting extends Model
{
    public const DEFAULT_SIGNUP_ENABLED = false;
    public const DEFAULT_PASSWORD_RESET_ENABLED = true;

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
}
