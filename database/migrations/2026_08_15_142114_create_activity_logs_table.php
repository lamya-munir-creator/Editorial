<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action_type'); // e.g. edit_article, system_settings, add_article, delete_article, login
            $table->string('action_label'); // e.g. قام بتعديل المقال
            $table->string('target_name')->nullable(); // e.g. مستقبل الذكاء الاصطناعي
            $table->string('target_url')->nullable(); // e.g. /articles
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};