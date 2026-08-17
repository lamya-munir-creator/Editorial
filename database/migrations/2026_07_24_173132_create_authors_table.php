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
        Schema::create('authors', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            // One user = one author profile
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            // Author avatar
            $table->foreignId('avatar_id')
                ->nullable()
                ->constrained('media')
                ->nullOnDelete();

            // Public author profile
            $table->string('display_name', 150);
            $table->string('slug', 180)->unique();

            $table->text('biography')->nullable();
            $table->string('job_title', 150)->nullable();

            // Social / professional links
            $table->string('website', 255)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->string('twitter', 255)->nullable();
            $table->string('linkedin', 255)->nullable();
            $table->string('instagram', 255)->nullable();
            $table->string('youtube', 255)->nullable();

            // Author information
            $table->enum('gender', [
                'male',
                'female',
                'other',
            ])->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};