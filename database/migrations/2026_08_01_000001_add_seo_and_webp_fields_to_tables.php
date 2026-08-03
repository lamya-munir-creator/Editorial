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
        if (Schema::hasTable('media') && !Schema::hasColumn('media', 'webp_path')) {
            Schema::table('media', function (Blueprint $table) {
                $table->string('webp_path', 500)->nullable()->after('path');
            });
        }

        if (Schema::hasTable('articles') && !Schema::hasColumn('articles', 'schema_type')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('schema_type', 50)->default('NewsArticle')->after('meta_description');
                $table->boolean('is_indexable')->default(true)->after('schema_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('media') && Schema::hasColumn('media', 'webp_path')) {
            Schema::table('media', function (Blueprint $table) {
                $table->dropColumn('webp_path');
            });
        }

        if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'schema_type')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn(['schema_type', 'is_indexable']);
            });
        }
    }
};
