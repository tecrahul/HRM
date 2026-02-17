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
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_broadcast')->default(false)->index();
            $table->boolean('read_status')->default(false)->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->index(['receiver_id', 'read_status', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
