<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id('setting_id');
            $table->string('setting_key')->unique();
            $table->text('setting_value')->nullable();
            $table->string('value_type')->default('string');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('system_activities', function (Blueprint $table) {
            $table->id('activity_id');
            $table->foreignId('actor_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_activities');
        Schema::dropIfExists('system_settings');
    }
};
