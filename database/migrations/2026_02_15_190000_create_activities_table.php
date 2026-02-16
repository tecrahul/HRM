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
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_key', 100)->index();
            $table->string('title', 180);
            $table->string('meta', 255)->nullable();
            $table->string('tone', 20)->default('#7c3aed');
            $table->nullableMorphs('subject');
            $table->json('payload')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->timestamps();

            $table->index(['actor_user_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
