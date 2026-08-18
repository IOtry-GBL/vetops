<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id('consultation_id');
            $table->foreignId('appointment_id')->constrained('appointments', 'appointment_id')->onDelete('cascade');
            $table->foreignId('veterinarian_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->text('chief_complaint')->notNullable();
            $table->text('ai_guidance_summary')->nullable();
            $table->text('consultation_notes')->nullable();
            $table->dateTime('consultation_date')->notNullable();
            $table->enum('status', ['Open', 'In Progress', 'Completed', 'Missed', 'Follow-up'])->notNullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
