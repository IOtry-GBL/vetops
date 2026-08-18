<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ai_predictions');
    }

    public function down(): void
    {
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->id('prediction_id');
            $table->foreignId('pet_id')->constrained('pets', 'pet_id')->onDelete('cascade');
            $table->foreignId('record_id')->constrained('symptom_logs', 'id')->onDelete('cascade');
            $table->string('predicted_condition');
            $table->enum('risk_level', ['Low', 'Medium', 'High'])->notNullable();
            $table->boolean('is_breed_related')->default(false);
            $table->float('confidence_score')->notNullable();
            $table->text('recommendation')->nullable();
            $table->string('model_version');
            $table->timestamps();
        });
    }
};
