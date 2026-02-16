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
        if (! Schema::hasColumn('leave_requests', 'half_day_session')) {
            Schema::table('leave_requests', function (Blueprint $table): void {
                $table->string('half_day_session', 30)->nullable()->index()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('leave_requests', 'half_day_session')) {
            Schema::table('leave_requests', function (Blueprint $table): void {
                $table->dropColumn('half_day_session');
            });
        }
    }
};
