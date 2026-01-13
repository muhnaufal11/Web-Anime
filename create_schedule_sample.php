<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Anime;
use App\Models\Schedule;

echo "=== Membuat Sample Data Jadwal ===" . PHP_EOL;
echo PHP_EOL;

// Get some anime
$animes = Anime::limit(7)->get();

if ($animes->count() < 7) {
    echo "❌ Tidak cukup anime untuk membuat jadwal (minimal 7 anime diperlukan)" . PHP_EOL;
    exit;
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

foreach ($days as $index => $day) {
    if (isset($animes[$index])) {
        $anime = $animes[$index];
        
        // Check if schedule already exists
        $existing = Schedule::where('anime_id', $anime->id)->first();
        if ($existing) {
            echo "⚠️  {$anime->title} sudah memiliki jadwal, skip..." . PHP_EOL;
            continue;
        }
        
        $schedule = Schedule::create([
            'anime_id' => $anime->id,
            'day_of_week' => $day,
            'broadcast_time' => sprintf('%02d:%02d:%02d', 19 + ($index % 5), 30, 0), // 19:30, 20:30, 21:30, 22:30, 23:30
            'next_episode_date' => now()->addWeek()->format('Y-m-d'),
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
            'notes' => 'Episode baru setiap minggu!',
        ]);
        
        echo "✓ {$day}: {$anime->title} - {$schedule->broadcast_time}" . PHP_EOL;
    }
}

echo PHP_EOL;
echo "✓ Sample data jadwal berhasil dibuat!" . PHP_EOL;
echo "Akses: http://127.0.0.1:8000/schedule" . PHP_EOL;
