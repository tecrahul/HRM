<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollStructure extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'basic_salary',
        'hra',
        'special_allowance',
        'bonus',
        'other_allowance',
        'pf_deduction',
        'tax_deduction',
        'other_deduction',
        'effective_from',
        'notes',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'hra' => 'decimal:2',
            'special_allowance' => 'decimal:2',
            'bonus' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'pf_deduction' => 'decimal:2',
            'tax_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PayrollStructureHistory::class)->orderByDesc('changed_at');
    }
}
