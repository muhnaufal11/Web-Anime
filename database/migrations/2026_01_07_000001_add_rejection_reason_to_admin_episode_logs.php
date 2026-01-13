<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('admin_episode_logs', function (Blueprint $table) {
            // Alasan penolakan (untuk status rejected)
            $table->text('rejection_reason')->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('admin_episode_logs', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
