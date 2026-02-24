<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicateMonths = DB::table('payroll_month_locks')
            ->select('payroll_month')
            ->groupBy('payroll_month')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('payroll_month');

        foreach ($duplicateMonths as $month) {
            $keepId = DB::table('payroll_month_locks')
                ->whereDate('payroll_month', (string) $month)
                ->orderByDesc('id')
                ->value('id');

            if ($keepId === null) {
                continue;
            }

            DB::table('payroll_month_locks')
                ->whereDate('payroll_month', (string) $month)
                ->where('id', '!=', (int) $keepId)
                ->delete();
        }

        Schema::table('payroll_month_locks', function (Blueprint $table): void {
            $table->unique('payroll_month', 'payroll_month_locks_month_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_month_locks', function (Blueprint $table): void {
            $table->dropUnique('payroll_month_locks_month_unique');
        });
    }
};
