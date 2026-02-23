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
        Schema::table('branches', function (Blueprint $table): void {
            if (! Schema::hasColumn('branches', 'address_line_1')) {
                $table->string('address_line_1', 255)->nullable()->after('code');
            }
            if (! Schema::hasColumn('branches', 'address_line_2')) {
                $table->string('address_line_2', 255)->nullable()->after('address_line_1');
            }
            if (! Schema::hasColumn('branches', 'city')) {
                $table->string('city', 120)->nullable()->after('address_line_2');
            }
            if (! Schema::hasColumn('branches', 'state')) {
                $table->string('state', 120)->nullable()->after('city');
            }
            if (! Schema::hasColumn('branches', 'country')) {
                $table->string('country', 120)->nullable()->after('state');
            }
            if (! Schema::hasColumn('branches', 'postal_code')) {
                $table->string('postal_code', 40)->nullable()->after('country');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $columns = [
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'country',
                'postal_code',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
