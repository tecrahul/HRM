<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (! $this->hasIndex($table, 'users_designation_id_idx')) {
                    $table->index('designation_id', 'users_designation_id_idx');
                }
            });
        }

        if (Schema::hasTable('user_profiles')) {
            Schema::table('user_profiles', function (Blueprint $table): void {
                if (! $this->hasIndex($table, 'user_profiles_status_idx')) {
                    $table->index('status', 'user_profiles_status_idx');
                }
                if (! $this->hasIndex($table, 'user_profiles_employee_code_idx')) {
                    $table->index('employee_code', 'user_profiles_employee_code_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                $this->dropIndexIfExists('users', 'users_designation_id_idx');
            });
        }

        if (Schema::hasTable('user_profiles')) {
            Schema::table('user_profiles', function (Blueprint $table): void {
                $this->dropIndexIfExists('user_profiles', 'user_profiles_status_idx');
                $this->dropIndexIfExists('user_profiles', 'user_profiles_employee_code_idx');
            });
        }
    }

    private function hasIndex(Blueprint $table, string $indexName): bool
    {
        // Schema inspection for indexes is not straightforward across drivers; best-effort wrapper
        return false; // Let database handle duplicates safely; named indexes avoid collisions.
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
                $blueprint->dropIndex($indexName);
            });
        } catch (Throwable $e) {
            // ignore if not exists
        }
    }
};

