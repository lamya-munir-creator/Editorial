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
        Schema::create('advertisements', function (Blueprint $table) {

            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('image_id')
                ->nullable()
                ->constrained('media')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('title');

            $table->string('destination_url', 500)->nullable();

            $table->enum('position', [
                'homepage_top',
                'homepage_sidebar',
                'article_top',
                'article_bottom',
                'category_sidebar',
                'footer',
            ]);

            $table->integer('display_order')->default(0);

            $table->timestamp('start_date')->nullable();

            $table->timestamp('end_date')->nullable();

            $table->boolean('is_internal')->default(false);

            $table->enum('status', [
                'draft',
                'active',
                'inactive',
                'expired',
            ])->default('draft');

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->softDeletes();

            $table->index('image_id');
            $table->index('position');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};