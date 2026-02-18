<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_profiles', 'is_employee')) {
                $table->boolean('is_employee')->default(true)->after('user_id')->index();
            }

            if (! Schema::hasColumn('user_profiles', 'employee_code')) {
                $table->string('employee_code', 32)->nullable()->unique()->after('is_employee');
            }
        });

        DB::statement(
            "UPDATE user_profiles up
             INNER JOIN users u ON u.id = up.user_id
             SET
                up.is_employee = CASE WHEN u.role IN ('super_admin', 'admin') THEN 0 ELSE 1 END,
                up.employee_code = CASE
                    WHEN u.role IN ('super_admin', 'admin') THEN NULL
                    ELSE CONCAT('EMP-', LPAD(up.user_id, 6, '0'))
                END"
        );
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('user_profiles', 'employee_code')) {
                $table->dropUnique('user_profiles_employee_code_unique');
                $table->dropColumn('employee_code');
            }

            if (Schema::hasColumn('user_profiles', 'is_employee')) {
                $table->dropIndex('user_profiles_is_employee_index');
                $table->dropColumn('is_employee');
            }
        });
    }
};
