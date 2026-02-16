<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'financial_year_start_month' => 'integer',
        ];
    }
}
