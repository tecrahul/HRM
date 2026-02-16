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
        if (! Schema::hasColumn('user_profiles', 'avatar_url')) {
            Schema::table('user_profiles', function (Blueprint $table): void {
                $table->string('avatar_url', 255)->nullable()->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_profiles', 'avatar_url')) {
            Schema::table('user_profiles', function (Blueprint $table): void {
                $table->dropColumn('avatar_url');
            });
        }
    }
};
