<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: moved before `users` (was 173131) because `users.avatar_id`
     * references `media`, while `media.uploaded_by/created_by/updated_by`
     * reference `users` — a circular dependency between the two tables.
     * We break the cycle by creating the plain columns here (no FK yet)
     * and adding all four foreign keys in
     * 2026_07_24_173131_add_foreign_keys_to_media_and_users_tables.php
     * once both tables exist.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('uploaded_by');
            $table->string('file_name', 255);
            $table->string('original_name', 255);
            $table->string('disk', 100)->default('public');
            $table->string('path', 500);
            $table->string('mime_type', 120);
            $table->string('extension', 20)->nullable();
            $table->bigInteger('file_size');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->text('caption')->nullable();
            $table->enum('type', ["image", "video", "audio", "document"]);
            $table->enum('visibility', ["public", "private"]);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index('uploaded_by');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('type');
            $table->index('visibility');
            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
