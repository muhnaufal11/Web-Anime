<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Episode Terbaru (Unique Episodes) ===" . PHP_EOL;
echo PHP_EOL;

// Query yang sama dengan HomeController
$latestEpisodesData = DB::table('episodes')
    ->join('animes', 'episodes.anime_id', '=', 'animes.id')
    ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
    ->where('video_servers.is_active', true)
    ->select(
        'episodes.id as episode_id',
        'animes.id as anime_id',
        'animes.title',
        'episodes.episode_number',
        DB::raw('MAX(video_servers.updated_at) as latest_server_update')
    )
    ->groupBy('episodes.id', 'animes.id', 'animes.title', 'episodes.episode_number')
    ->orderBy('latest_server_update', 'desc')
    ->limit(12)
    ->get();

foreach($latestEpisodesData as $index => $row) {
    echo ($index + 1) . ". {$row->title} EP{$row->episode_number} - {$row->latest_server_update}" . PHP_EOL;
}

echo PHP_EOL;
echo "Total: " . $latestEpisodesData->count() . " episodes unik dengan video server" . PHP_EOL;
