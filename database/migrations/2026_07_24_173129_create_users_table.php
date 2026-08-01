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
        if (!Schema::hasTable('users')) {
            Schema::disableForeignKeyConstraints();

            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->bigInteger('role_id');
                $table->string('first_name', 100);
                $table->string('last_name', 100)->nullable();
                $table->string('username', 100)->unique();
                $table->string('email', 255)->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password', 255);
                $table->string('phone', 30)->nullable();
                $table->bigInteger('avatar_id')->nullable();
                $table->string('locale', 10)->default('ar');
                $table->string('timezone', 100)->nullable();
                $table->string('remember_token', 100)->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->enum('status', ["active","inactive","suspended"]);
                $table->bigInteger('created_by')->nullable();
                $table->bigInteger('updated_by')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->index('role_id');
                $table->index('username');
                $table->index('email');
                $table->index('status');
            });

            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep intact if needed
    }
};
