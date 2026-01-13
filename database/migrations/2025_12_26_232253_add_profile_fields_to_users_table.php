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
            $table->string('avatar')->nullable()->after('password'); // Avatar profile
            $table->text('bio')->nullable()->after('avatar'); // Bio
            $table->string('phone')->nullable()->after('bio'); // No telepon
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('phone'); // Jenis kelamin
            $table->date('birth_date')->nullable()->after('gender'); // Tanggal lahir
            $table->string('location')->nullable()->after('birth_date'); // Lokasi
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
            $table->dropColumn(['avatar', 'bio', 'phone', 'gender', 'birth_date', 'location']);
        });
    }
};
