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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('author_id')->constrained('authors');
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('featured_image_id')->nullable()->constrained('media');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('excerpt')->nullable();
            // was $table->string('content') (varchar 255) — far too short for
            // an article body, changed to longText.
            $table->longText('content');
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->smallInteger('reading_time')->default(1);
            $table->integer('views_count')->default(0);
            $table->boolean('is_featured')->default(0);
            $table->boolean('allow_comments')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->enum('status', ["draft", "published", "archived"]);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index('slug');
            $table->index('status');
            $table->index('published_at');
            $table->index('is_featured');
            $table->index('views_count');
            $table->index(['category_id', 'status']);
            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
