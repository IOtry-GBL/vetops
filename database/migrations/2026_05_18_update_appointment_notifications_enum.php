<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Update the ENUM to include all appointment statuses
        // For MySQL, we need to modify the column definition
        DB::statement("ALTER TABLE appointment_notifications MODIFY notification_status ENUM('Pending', 'Approved', 'Completed', 'Missed', 'Cancelled', 'Rescheduled', 'deleted') DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE appointment_notifications MODIFY notification_status ENUM('sent', 'deleted') DEFAULT 'sent'");
    }
};
