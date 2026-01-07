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
        // Change enum to varchar to allow more source types
        DB::statement("ALTER TABLE video_servers MODIFY COLUMN source VARCHAR(50) DEFAULT 'manual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to enum (will lose data that doesn't match)
        DB::statement("ALTER TABLE video_servers MODIFY COLUMN source ENUM('sync', 'manual') DEFAULT 'manual'");
    }
};
