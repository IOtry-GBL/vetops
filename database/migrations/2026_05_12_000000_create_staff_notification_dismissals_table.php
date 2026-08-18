<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_notification_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('notification_key', 120);
            $table->timestamps();

            $table->unique(['user_id', 'notification_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_notification_dismissals');
    }
};
