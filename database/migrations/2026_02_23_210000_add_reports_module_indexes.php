<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'start_date', 'end_date', 'status'],
                'leave_requests_user_period_status_idx'
            );
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->index(
                ['occurred_at', 'event_key'],
                'activities_occurred_event_key_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->dropIndex('leave_requests_user_period_status_idx');
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->dropIndex('activities_occurred_event_key_idx');
        });
    }
};
