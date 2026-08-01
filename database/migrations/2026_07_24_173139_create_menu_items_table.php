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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('pages');
            $table->foreignId('article_id')->nullable()->constrained('articles');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->string('title', 255);
            $table->string('url', 500)->nullable();
            $table->string('target', 20)->default('_self');
            $table->string('icon', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
