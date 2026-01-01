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
        Schema::table('watch_history', function (Blueprint $table) {
            // Add duration in seconds to store episode length for accurate progress calculation
            if (!Schema::hasColumn('watch_history', 'duration')) {
                $table->integer('duration')->default(1440)->after('progress'); // default 24 minutes (1440 seconds)
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watch_history', function (Blueprint $table) {
            if (Schema::hasColumn('watch_history', 'duration')) {
                $table->dropColumn('duration');
            }
        });
    }
};
