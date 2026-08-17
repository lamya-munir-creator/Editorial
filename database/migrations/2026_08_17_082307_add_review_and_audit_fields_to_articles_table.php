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
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('submitted_for_review_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_for_review_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });

        DB::statement("ALTER TABLE articles MODIFY COLUMN status ENUM('draft', 'pending_review', 'published', 'archived') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE articles MODIFY COLUMN status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft'");

        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['submitted_for_review_by']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'submitted_for_review_by',
                'submitted_for_review_at',
                'reviewed_by',
                'reviewed_at',
            ]);
        });
    }
};
