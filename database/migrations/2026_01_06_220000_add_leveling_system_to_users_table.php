<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan kolom untuk sistem leveling "Hard Mode"
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Jumlah bulan berturut-turut memenuhi target (untuk naik level)
            $table->unsignedTinyInteger('consecutive_success_months')->default(0)->after('admin_notes');
            
            // Tanggal terakhir evaluasi bulanan dilakukan
            $table->date('last_evaluation_date')->nullable()->after('consecutive_success_months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'consecutive_success_months',
                'last_evaluation_date'
            ]);
        });
    }
};
