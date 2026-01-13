<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Episode;
use App\Models\VideoServer;

echo "=== Update Video Server untuk Simulasi ===" . PHP_EOL;
echo PHP_EOL;

// Get episodes dengan video servers
$episodes = Episode::whereHas('videoServers')
    ->with('anime', 'videoServers')
    ->take(8)
    ->get();

foreach ($episodes as $index => $episode) {
    // Update 1 video server per episode
    $server = $episode->videoServers->random();
    if ($server) {
        // Update dengan waktu yang berbeda
        $timestamp = now()->subMinutes($index * 5);
        $server->updated_at = $timestamp;
        $server->save();
        
        echo ($index + 1) . ". {$episode->anime->title} EP{$episode->episode_number} [{$server->server_name}] - {$timestamp}" . PHP_EOL;
    }
}

echo PHP_EOL;
echo "✓ Selesai! Refresh halaman untuk melihat perubahan." . PHP_EOL;
