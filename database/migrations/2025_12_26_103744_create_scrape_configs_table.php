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
        Schema::create('scrape_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('source', ['myanimelist', 'animesail', 'both'])->default('both');
            $table->enum('sync_type', ['metadata', 'episodes', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_sync')->default(false);
            $table->string('schedule')->nullable(); // cron expression
            $table->integer('max_items')->default(50);
            $table->json('filters')->nullable(); // genre filters, status filters, etc
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scrape_configs');
    }
};
