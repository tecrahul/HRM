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
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 30)->index(); // direct|broadcast_all|broadcast_team
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('direct_user_low_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('direct_user_high_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 160)->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['direct_user_low_id', 'direct_user_high_id']);
            $table->index(['type', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
