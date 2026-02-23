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
        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('approval_status', 24)->default('pending')->index()->after('status');
            $table->text('approval_note')->nullable()->after('notes');
            $table->foreignId('approved_by_user_id')->nullable()->after('marked_by_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->foreignId('rejected_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');

            $table->foreignId('correction_requested_by_user_id')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->timestamp('correction_requested_at')->nullable()->after('correction_requested_by_user_id');
            $table->text('correction_reason')->nullable()->after('correction_requested_at');
            $table->dateTime('requested_check_in_at')->nullable()->after('correction_reason');
            $table->dateTime('requested_check_out_at')->nullable()->after('requested_check_in_at');
            $table->unsignedInteger('requested_work_minutes')->nullable()->after('requested_check_out_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['rejected_by_user_id']);
            $table->dropForeign(['correction_requested_by_user_id']);

            $table->dropIndex(['approval_status']);

            $table->dropColumn([
                'approval_status',
                'approval_note',
                'approved_by_user_id',
                'approved_at',
                'rejected_by_user_id',
                'rejected_at',
                'correction_requested_by_user_id',
                'correction_requested_at',
                'correction_reason',
                'requested_check_in_at',
                'requested_check_out_at',
                'requested_work_minutes',
            ]);
        });
    }
};

