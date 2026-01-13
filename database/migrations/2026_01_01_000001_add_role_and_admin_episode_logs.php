<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('is_admin');
            $table->index('role');
        });

        // Migrate existing admins to the new role column
        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);

        Schema::table('episodes', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('slug')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('admin_episode_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('episode_id')->constrained('episodes')->onDelete('cascade');
            $table->unsignedInteger('amount')->default(500);
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'episode_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_episode_logs');

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
