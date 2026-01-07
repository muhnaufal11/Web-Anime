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
        Schema::table('users', function (Blueprint $table) {
            // Admin level: 1 = baru (limit), 2 = senior (limit lebih tinggi), 0 = unlimited
            $table->tinyInteger('admin_level')->default(1)->after('payment_rate');
            
            // Monthly withdrawal limit (null = use default based on role & level)
            $table->integer('monthly_limit')->nullable()->after('admin_level');
            
            // Rollover balance - saldo yang belum dibayar karena melebihi limit
            $table->integer('rollover_balance')->default(0)->after('monthly_limit');
            
            // Track tanggal mulai kerja untuk evaluasi 3 bulan
            $table->date('admin_start_date')->nullable()->after('rollover_balance');
            
            // Catatan evaluasi
            $table->text('admin_notes')->nullable()->after('admin_start_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'admin_level',
                'monthly_limit',
                'rollover_balance',
                'admin_start_date',
                'admin_notes'
            ]);
        });
    }
};
