<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('payroll_month')->index();
            $table->decimal('working_days', 5, 2)->default(0);
            $table->decimal('attendance_lop_days', 5, 2)->default(0);
            $table->decimal('unpaid_leave_days', 5, 2)->default(0);
            $table->decimal('lop_days', 5, 2)->default(0);
            $table->decimal('payable_days', 5, 2)->default(0);
            $table->decimal('basic_pay', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('special_allowance', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('gross_earnings', 12, 2)->default(0);
            $table->decimal('pf_deduction', 12, 2)->default(0);
            $table->decimal('tax_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at')->nullable()->index();
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'payroll_month']);
            $table->index(['status', 'payroll_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
