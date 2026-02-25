<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->timestamp('sender_trashed_at')->nullable()->after('read_at');
            $table->timestamp('receiver_trashed_at')->nullable()->after('sender_trashed_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropColumn(['sender_trashed_at', 'receiver_trashed_at']);
        });
    }
};

