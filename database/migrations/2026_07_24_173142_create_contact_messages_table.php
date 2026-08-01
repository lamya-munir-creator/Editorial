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
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('full_name', 150);
            $table->string('email', 255);
            $table->string('phone', 30)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->enum('status', ["new", "read", "replied", "archived"]);
            $table->foreignId('handled_by')->nullable()->constrained('users');
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index('email');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
