<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Anime dengan Video Server (Latest 15) ===" . PHP_EOL;
echo PHP_EOL;

$data = DB::table('animes')
    ->join('episodes', 'animes.id', '=', 'episodes.anime_id')
    ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
    ->where('video_servers.is_active', true)
    ->select(
        'animes.id as anime_id',
        'animes.title',
        'episodes.episode_number',
        'episodes.id as episode_id',
        'video_servers.server_name',
        'video_servers.updated_at'
    )
    ->orderBy('video_servers.updated_at', 'desc')
    ->limit(15)
    ->get();

foreach($data as $row) {
    echo "#{$row->anime_id} {$row->title} EP{$row->episode_number} [{$row->server_name}] - {$row->updated_at}" . PHP_EOL;
}

echo PHP_EOL;
echo "Total: " . $data->count() . " episodes dengan video server" . PHP_EOL;
