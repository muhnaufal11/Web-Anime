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
        Schema::create('anime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('mal_url')->nullable();
            $table->integer('mal_id')->nullable();
            $table->text('reason')->nullable();
            $table->enum('type', ['new_anime', 'add_episodes'])->default('new_anime');
            $table->foreignId('anime_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('upvotes')->default(0);
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('mal_id');
        });
        
        Schema::create('anime_request_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anime_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['anime_request_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anime_request_votes');
        Schema::dropIfExists('anime_requests');
    }
};
