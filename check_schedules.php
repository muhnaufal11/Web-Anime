<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Schedule;

echo "=== Checking Invalid Time Format ===" . PHP_EOL;
echo PHP_EOL;

// Get all schedules
$schedules = Schedule::all();

echo "Total schedules: " . $schedules->count() . PHP_EOL;
echo PHP_EOL;

foreach ($schedules as $schedule) {
    echo "ID: {$schedule->id}" . PHP_EOL;
    echo "  Anime: {$schedule->anime->title}" . PHP_EOL;
    echo "  Day: {$schedule->day_of_week}" . PHP_EOL;
    echo "  Broadcast Time (raw): " . $schedule->getAttributes()['broadcast_time'] . PHP_EOL;
    echo "  Next Episode: {$schedule->next_episode_date}" . PHP_EOL;
    echo PHP_EOL;
}

// Delete all schedules with invalid time
echo "Deleting invalid schedules..." . PHP_EOL;
$deleted = Schedule::query()->delete();
echo "✓ Deleted {$deleted} schedules" . PHP_EOL;
