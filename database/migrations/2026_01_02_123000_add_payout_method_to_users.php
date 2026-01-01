<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('payout_method')->nullable()->after('bank_account_holder');
            $table->string('payout_wallet_provider')->nullable()->after('payout_method');
            $table->string('payout_wallet_number')->nullable()->after('payout_wallet_provider');
            $table->string('payout_notes')->nullable()->after('payout_wallet_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'payout_method',
                'payout_wallet_provider',
                'payout_wallet_number',
                'payout_notes',
            ]);
        });
    }
};
