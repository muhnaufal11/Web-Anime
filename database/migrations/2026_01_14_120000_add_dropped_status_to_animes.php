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
        // Modify enum to add 'Dropped' status
        DB::statement("ALTER TABLE animes MODIFY COLUMN status ENUM('Ongoing', 'Completed', 'Dropped') DEFAULT 'Ongoing'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum (will fail if there are 'Dropped' values)
        DB::statement("ALTER TABLE animes MODIFY COLUMN status ENUM('Ongoing', 'Completed') DEFAULT 'Ongoing'");
    }
};
