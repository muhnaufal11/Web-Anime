<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('view_token', 64)->nullable()->unique()->after('id');
            $table->boolean('is_closed')->default(false)->after('replied_at');
            $table->timestamp('closed_at')->nullable()->after('is_closed');
        });
    }

    public function down()
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn(['view_token', 'is_closed', 'closed_at']);
        });
    }
};