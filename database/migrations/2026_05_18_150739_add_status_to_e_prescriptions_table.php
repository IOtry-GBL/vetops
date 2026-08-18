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
        Schema::table('e_prescriptions', function (Blueprint $table) {
            $table->enum('status', ['ongoing', 'completed'])->default('ongoing')->after('issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('e_prescriptions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
