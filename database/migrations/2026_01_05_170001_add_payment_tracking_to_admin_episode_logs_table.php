<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_episode_logs', function (Blueprint $table) {
            // Tanggal pembayaran
            $table->timestamp('paid_at')->nullable()->after('note');
            
            // Bulan pembayaran (untuk tracking)
            $table->date('payment_month')->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_episode_logs', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'payment_month']);
        });
    }
};
