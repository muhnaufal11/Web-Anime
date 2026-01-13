<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Anime;
use App\Models\Episode;

echo "=== Simulasi HomeController Logic (Updated) ===" . PHP_EOL;
echo PHP_EOL;

// Same logic as HomeController
$latestEpisodesData = DB::table('episodes')
    ->join('animes', 'episodes.anime_id', '=', 'animes.id')
    ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
    ->where('video_servers.is_active', true)
    ->select(
        'episodes.id as episode_id',
        'animes.id as anime_id',
        DB::raw('MAX(video_servers.updated_at) as latest_server_update')
    )
    ->groupBy('episodes.id', 'animes.id')
    ->orderBy('latest_server_update', 'desc')
    ->limit(12)
    ->get();

$episodeIds = $latestEpisodesData->pluck('episode_id');

echo "Episode IDs from query: " . $episodeIds->implode(', ') . PHP_EOL;
echo PHP_EOL;

$episodes = Episode::whereIn('id', $episodeIds)
    ->with(['anime.genres', 'videoServers' => fn($q) => $q->where('is_active', true)])
    ->get()
    ->sortBy(function($episode) use ($latestEpisodesData) {
        $match = $latestEpisodesData->firstWhere('episode_id', $episode->id);
        return $match ? $latestEpisodesData->search($match) : 999;
    })
    ->values();

echo "Loaded episodes count: " . $episodes->count() . PHP_EOL;
foreach ($episodes as $ep) {
    echo "  - Episode ID {$ep->id}: {$ep->anime->title} EP{$ep->episode_number}" . PHP_EOL;
}
echo PHP_EOL;

$latestEpisodes = $episodes->map(function($episode) {
    $anime = clone $episode->anime;
    $anime->setRelation('episodes', collect([$episode]));
    return $anime;
});

echo "Total Entries di Latest Episodes: " . $latestEpisodes->count() . PHP_EOL;
echo PHP_EOL;

foreach ($latestEpisodes as $index => $anime) {
    $episode = $anime->episodes->first();
    if ($episode) {
        $match = $latestEpisodesData->firstWhere('episode_id', $episode->id);
        echo ($index + 1) . ". {$anime->title} - EP{$episode->episode_number} (Updated: {$match->latest_server_update})" . PHP_EOL;
    }
}

echo PHP_EOL;
echo "✓ Data siap ditampilkan di homepage!" . PHP_EOL;
