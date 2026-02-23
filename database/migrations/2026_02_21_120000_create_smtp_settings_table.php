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
        Schema::create('smtp_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('mail_driver', 40);
            $table->string('mail_host', 120);
            $table->unsignedInteger('mail_port');
            $table->string('mail_username', 120)->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_encryption', 40)->nullable();
            $table->string('from_address', 120)->nullable();
            $table->string('from_name', 120)->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_settings');
    }
};
