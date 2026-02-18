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
        Schema::table('payrolls', function (Blueprint $table): void {
            if (! Schema::hasColumn('payrolls', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()->after('generated_by_user_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('payrolls', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by_user_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table): void {
            if (Schema::hasColumn('payrolls', 'approved_by_user_id')) {
                $table->dropConstrainedForeignId('approved_by_user_id');
            }

            if (Schema::hasColumn('payrolls', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
