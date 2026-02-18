<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';

    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    public const PAYMENT_UPI = 'upi';
    public const PAYMENT_CASH = 'cash';
    public const PAYMENT_CHEQUE = 'cheque';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'payroll_month',
        'working_days',
        'attendance_lop_days',
        'unpaid_leave_days',
        'lop_days',
        'payable_days',
        'basic_pay',
        'hra',
        'special_allowance',
        'bonus',
        'other_allowance',
        'gross_earnings',
        'pf_deduction',
        'tax_deduction',
        'other_deduction',
        'total_deductions',
        'net_salary',
        'status',
        'notes',
        'generated_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'paid_by_user_id',
        'paid_at',
        'payment_method',
        'payment_reference',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payroll_month' => 'date',
            'working_days' => 'decimal:2',
            'attendance_lop_days' => 'decimal:2',
            'unpaid_leave_days' => 'decimal:2',
            'lop_days' => 'decimal:2',
            'payable_days' => 'decimal:2',
            'basic_pay' => 'decimal:2',
            'hra' => 'decimal:2',
            'special_allowance' => 'decimal:2',
            'bonus' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'gross_earnings' => 'decimal:2',
            'pf_deduction' => 'decimal:2',
            'tax_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PROCESSED,
            self::STATUS_PAID,
            self::STATUS_FAILED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_BANK_TRANSFER,
            self::PAYMENT_UPI,
            self::PAYMENT_CASH,
            self::PAYMENT_CHEQUE,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'entity_id')
            ->where('entity_type', 'payroll');
    }
}
