<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug Episode IDs ===" . PHP_EOL;
echo PHP_EOL;

$data = DB::table('episodes')
    ->join('animes', 'episodes.anime_id', '=', 'animes.id')
    ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
    ->where('video_servers.is_active', true)
    ->select(
        'episodes.id as episode_id',
        'animes.id as anime_id',
        'animes.title',
        'episodes.episode_number',
        DB::raw('MAX(video_servers.updated_at) as latest')
    )
    ->groupBy('episodes.id', 'animes.id', 'animes.title', 'episodes.episode_number')
    ->orderBy('latest', 'desc')
    ->limit(5)
    ->get();

foreach($data as $r) {
    echo "Episode ID: {$r->episode_id} - {$r->title} EP{$r->episode_number} - {$r->latest}" . PHP_EOL;
}
