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
        Schema::table('holidays', function (Blueprint $table): void {
            if (! Schema::hasColumn('holidays', 'end_date')) {
                $table->date('end_date')->nullable()->after('holiday_date');
            }

            if (! Schema::hasColumn('holidays', 'holiday_type')) {
                $table->string('holiday_type', 30)->default('public')->after('branch_id');
            }

            if (! Schema::hasColumn('holidays', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('holiday_type');
            }
        });

        DB::table('holidays')->update([
            'holiday_type' => DB::raw("CASE WHEN is_optional = 1 THEN 'optional' ELSE 'public' END"),
        ]);

        DB::table('holidays')
            ->whereNull('is_active')
            ->update(['is_active' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table): void {
            if (Schema::hasColumn('holidays', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('holidays', 'holiday_type')) {
                $table->dropColumn('holiday_type');
            }

            if (Schema::hasColumn('holidays', 'end_date')) {
                $table->dropColumn('end_date');
            }
        });
    }
};
