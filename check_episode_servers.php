<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Episode;

echo "=== Cek Episode Haikyuu ===" . PHP_EOL;
echo PHP_EOL;

$ep = Episode::where('slug', 'haikyuu-to-the-top-episode-1')->first();

if (!$ep) {
    echo "❌ Episode tidak ditemukan dengan slug 'haikyuu-to-the-top-episode-1'" . PHP_EOL;
    echo "Mencoba cari dengan title...";
    $ep = Episode::where('title', 'like', '%haikyuu%')->first();
    if ($ep) {
        echo " FOUND!" . PHP_EOL;
        echo "Episode: {$ep->title} (Slug: {$ep->slug})" . PHP_EOL;
    } else {
        echo " TIDAK ADA" . PHP_EOL;
    }
} else {
    echo "✅ Episode ditemukan!" . PHP_EOL;
    echo "Title: {$ep->title}" . PHP_EOL;
    echo "Slug: {$ep->slug}" . PHP_EOL;
    echo PHP_EOL;
    
    $servers = $ep->videoServers()->get();
    echo "Total VideoServers: {$servers->count()}" . PHP_EOL;
    
    if ($servers->count() > 0) {
        foreach ($servers as $s) {
            echo "  - {$s->server_name} (Active: {$s->is_active}, URL: {$s->embed_url})" . PHP_EOL;
        }
    } else {
        echo "❌ EPISODE INI TIDAK PUNYA VIDEO SERVER!" . PHP_EOL;
        echo PHP_EOL;
        echo "Cari episode lain yang punya video server..." . PHP_EOL;
        $episodesWithServers = Episode::whereHas('videoServers')->with('anime', 'videoServers')->limit(5)->get();
        if ($episodesWithServers->count() > 0) {
            echo PHP_EOL;
            echo "Episode dengan video server:" . PHP_EOL;
            foreach ($episodesWithServers as $ep2) {
                echo "  - {$ep2->anime->title} EP{$ep2->episode_number} ({$ep2->videoServers->count()} servers)" . PHP_EOL;
            }
        }
    }
}
echo PHP_EOL;
