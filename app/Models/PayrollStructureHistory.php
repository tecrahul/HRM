<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollStructureHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'payroll_structure_id',
        'user_id',
        'changed_by_user_id',
        'before_values',
        'after_values',
        'change_summary',
        'changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'change_summary' => 'array',
            'changed_at' => 'datetime',
        ];
    }

    public function payrollStructure(): BelongsTo
    {
        return $this->belongsTo(PayrollStructure::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
