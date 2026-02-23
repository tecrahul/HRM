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
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropUnique('departments_name_unique');
            $table->dropUnique('departments_code_unique');

            $table->foreignId('branch_id')
                ->nullable()
                ->after('code')
                ->constrained('branches')
                ->nullOnDelete();

            $table->unique(['branch_id', 'code'], 'departments_branch_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropUnique('departments_branch_code_unique');
            $table->dropConstrainedForeignId('branch_id');

            $table->unique('name');
            $table->unique('code');
        });
    }
};
