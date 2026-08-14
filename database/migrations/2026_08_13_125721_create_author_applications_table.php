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
        Schema::create('author_applications', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            // User who submitted the application
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Information submitted with the application
            $table->string('display_name', 150);
            $table->string('job_title', 150)->nullable();
            $table->text('biography')->nullable();
            $table->string('website', 255)->nullable();

            // Why the user wants to become an author
            $table->text('application_message')->nullable();

            // Application workflow
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            // Admin review
            $table->text('admin_notes')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_applications');
    }
};