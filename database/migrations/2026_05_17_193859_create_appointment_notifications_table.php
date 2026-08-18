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
        Schema::create('appointment_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->enum('notification_status', ['Pending', 'Approved', 'Completed', 'Missed', 'Cancelled', 'Rescheduled', 'deleted'])->default('Pending');
            $table->timestamps();
            
            // Foreign key
            $table->foreign('appointment_id')
                ->references('appointment_id')
                ->on('appointments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_notifications');
    }
};
