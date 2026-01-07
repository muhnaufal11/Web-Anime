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
        // Change enum to string to allow more flexibility
        DB::statement("ALTER TABLE advertisements MODIFY COLUMN position VARCHAR(50)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to enum if needed
        DB::statement("ALTER TABLE advertisements MODIFY COLUMN position ENUM(
            'header_top',
            'header_bottom',
            'sidebar_top',
            'sidebar_bottom',
            'content_top',
            'content_middle',
            'content_bottom',
            'footer',
            'popup',
            'floating',
            'video_overlay',
            'video_before',
            'video_after'
        )");
    }
};
