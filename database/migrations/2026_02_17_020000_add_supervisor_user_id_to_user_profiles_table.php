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
        if (! Schema::hasColumn('user_profiles', 'supervisor_user_id')) {
            Schema::table('user_profiles', function (Blueprint $table): void {
                $table->foreignId('supervisor_user_id')
                    ->nullable()
                    ->after('manager_name')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_profiles', 'supervisor_user_id')) {
            Schema::table('user_profiles', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('supervisor_user_id');
            });
        }
    }
};
