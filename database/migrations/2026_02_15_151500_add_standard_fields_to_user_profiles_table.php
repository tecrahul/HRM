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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('alternate_phone', 40)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('national_id', 80)->nullable();
            $table->string('work_location', 120)->nullable();
            $table->string('manager_name', 120)->nullable();
            $table->string('linkedin_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'alternate_phone',
                'date_of_birth',
                'gender',
                'marital_status',
                'nationality',
                'national_id',
                'work_location',
                'manager_name',
                'linkedin_url',
            ]);
        });
    }
};
