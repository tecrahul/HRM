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
        Schema::table('user_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_profiles', 'bank_account_name')) {
                $table->string('bank_account_name', 120)->nullable()->after('manager_name');
            }

            if (! Schema::hasColumn('user_profiles', 'bank_account_number')) {
                $table->string('bank_account_number', 60)->nullable()->after('bank_account_name');
            }

            if (! Schema::hasColumn('user_profiles', 'bank_ifsc')) {
                $table->string('bank_ifsc', 30)->nullable()->after('bank_account_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $drops = [];
            if (Schema::hasColumn('user_profiles', 'bank_ifsc')) {
                $drops[] = 'bank_ifsc';
            }
            if (Schema::hasColumn('user_profiles', 'bank_account_number')) {
                $drops[] = 'bank_account_number';
            }
            if (Schema::hasColumn('user_profiles', 'bank_account_name')) {
                $drops[] = 'bank_account_name';
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
