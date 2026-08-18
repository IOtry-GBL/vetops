<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('symptom_logs');
    }

    public function down(): void
    {
        Schema::create('symptom_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets', 'pet_id')->onDelete('cascade');
            $table->boolean('itching')->default(false);
            $table->boolean('hair_loss')->default(false);
            $table->boolean('redness')->default(false);
            $table->boolean('wounds')->default(false);
            $table->boolean('fever')->default(false);
            $table->boolean('vomiting')->default(false);
            $table->boolean('diarrhea')->default(false);
            $table->integer('duration_days')->nullable();
            $table->string('ai_prediction', 100)->nullable();
            $table->enum('concern_level', ['monitor', 'vet_visit', 'emergency'])->default('monitor');
            $table->timestamps();
        });
    }
};
