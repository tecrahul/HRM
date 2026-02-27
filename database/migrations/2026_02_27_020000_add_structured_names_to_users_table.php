<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name', 120)->after('id');
            $table->string('middle_name', 120)->nullable()->after('first_name');
            $table->string('last_name', 120)->after('middle_name');
        });

        // Backfill from existing `name` column
        DB::table('users')->select('id', 'name')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $name = trim((string) ($row->name ?? ''));
                $first = '';
                $last = '';

                if ($name !== '') {
                    $parts = preg_split('/\s+/', $name) ?: [];
                    if (count($parts) === 1) {
                        $first = $parts[0];
                    } elseif (count($parts) > 1) {
                        $first = array_shift($parts) ?? '';
                        $last = trim(implode(' ', $parts));
                    }
                }

                DB::table('users')->where('id', $row->id)->update([
                    'first_name' => $first !== '' ? $first : 'User',
                    'last_name' => $last !== '' ? $last : 'Account',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'middle_name', 'last_name']);
        });
    }
};

