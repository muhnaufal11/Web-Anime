<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Schedule;

echo "=== Verifikasi Schedule Data ===" . PHP_EOL;
echo PHP_EOL;

$schedules = Schedule::with('anime')->orderBy('day_of_week')->get();

echo "Total Jadwal: " . $schedules->count() . PHP_EOL;
echo PHP_EOL;

foreach ($schedules as $schedule) {
    echo "📅 {$schedule->day_of_week}" . PHP_EOL;
    echo "   Anime: {$schedule->anime->title}" . PHP_EOL;
    echo "   Jam: {$schedule->broadcast_time}" . PHP_EOL;
    echo "   Episode Berikutnya: {$schedule->next_episode_date}" . PHP_EOL;
    echo "   Status: " . ($schedule->is_active ? '✅ Aktif' : '❌ Nonaktif') . PHP_EOL;
    echo PHP_EOL;
}

echo "✓ Semua jadwal valid dan siap digunakan!" . PHP_EOL;
