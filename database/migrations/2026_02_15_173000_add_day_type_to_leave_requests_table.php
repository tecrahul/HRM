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
        if (! Schema::hasColumn('leave_requests', 'day_type')) {
            Schema::table('leave_requests', function (Blueprint $table): void {
                $table->string('day_type', 20)->default('full_day')->index()->after('leave_type');
            });
        }

        if (Schema::hasColumn('leave_requests', 'day_type')) {
            DB::table('leave_requests')
                ->whereNull('day_type')
                ->update(['day_type' => 'full_day']);

            DB::table('leave_requests')
                ->where('leave_type', 'half_day')
                ->update(['day_type' => 'half_day']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('leave_requests', 'day_type')) {
            Schema::table('leave_requests', function (Blueprint $table): void {
                $table->dropColumn('day_type');
            });
        }
    }
};
