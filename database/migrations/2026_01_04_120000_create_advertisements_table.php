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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama iklan untuk identifikasi
            $table->enum('position', [
                'header_top',      // Atas navbar
                'header_bottom',   // Bawah navbar
                'sidebar_top',     // Sidebar atas
                'sidebar_bottom',  // Sidebar bawah
                'content_top',     // Atas konten
                'content_middle',  // Tengah konten
                'content_bottom',  // Bawah konten
                'footer',          // Footer
                'popup',           // Popup
                'floating',        // Floating/sticky
            ]);
            $table->enum('type', ['adsense', 'custom', 'image', 'html']);
            $table->text('code')->nullable(); // Kode AdSense atau HTML custom
            $table->string('image_path')->nullable(); // Path gambar untuk tipe image
            $table->string('link')->nullable(); // Link untuk iklan image
            $table->string('size')->nullable(); // Ukuran iklan (728x90, 300x250, etc)
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // Urutan tampilan
            $table->timestamp('start_date')->nullable(); // Tanggal mulai
            $table->timestamp('end_date')->nullable(); // Tanggal berakhir
            $table->json('pages')->nullable(); // Halaman mana saja yang menampilkan iklan
            $table->boolean('show_on_mobile')->default(true);
            $table->boolean('show_on_desktop')->default(true);
            $table->unsignedBigInteger('impressions')->default(0); // Jumlah tampilan
            $table->unsignedBigInteger('clicks')->default(0); // Jumlah klik
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
