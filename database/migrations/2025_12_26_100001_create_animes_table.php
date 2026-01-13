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
        Schema::create('animes', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('slug')->unique();
            $table->longText('synopsis');
            $table->string('poster_image')->nullable();
            $table->enum('type', ['TV', 'Movie', 'ONA'])->default('TV');
            $table->enum('status', ['Ongoing', 'Completed'])->default('Ongoing');
            $table->unsignedInteger('release_year')->nullable();
            $table->float('rating')->nullable();
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animes');
    }
};
