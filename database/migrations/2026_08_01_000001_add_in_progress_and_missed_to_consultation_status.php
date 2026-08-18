<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE consultations MODIFY status ENUM('Open', 'In Progress', 'Completed', 'Missed', 'Follow-up') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE consultations MODIFY status ENUM('Open', 'Completed', 'Follow-up') NOT NULL");
    }
};
