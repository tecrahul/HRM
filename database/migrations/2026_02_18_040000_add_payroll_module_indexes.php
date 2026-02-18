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
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->index(['branch', 'department'], 'user_profiles_branch_department_idx');
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->index(['payroll_month', 'status', 'user_id'], 'payrolls_month_status_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropIndex('user_profiles_branch_department_idx');
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropIndex('payrolls_month_status_user_idx');
        });
    }
};
