<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('consultation_messages');
    }

    public function down(): void
    {
        Schema::create('consultation_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->foreignId('consultation_id')->constrained('consultations', 'consultation_id')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->text('message_body');
            $table->timestamps();
        });
    }
};
