<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Episode;
use App\Models\VideoServer;

echo "=== Demo: Otomatis Update Episode Terbaru ===" . PHP_EOL;
echo PHP_EOL;

// 1. Tampilkan episode terbaru saat ini
echo "1. Episode Terbaru Sebelum Penambahan Video Server:" . PHP_EOL;
echo "---------------------------------------------------" . PHP_EOL;
$current = DB::table('episodes')
    ->join('animes', 'episodes.anime_id', '=', 'animes.id')
    ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
    ->where('video_servers.is_active', true)
    ->select(
        'episodes.id as episode_id',
        'animes.title',
        'episodes.episode_number',
        DB::raw('MAX(video_servers.updated_at) as latest_update')
    )
    ->groupBy('episodes.id', 'animes.title', 'episodes.episode_number')
    ->orderBy('latest_update', 'desc')
    ->limit(5)
    ->get();

foreach ($current as $i => $ep) {
    echo ($i + 1) . ". {$ep->title} EP{$ep->episode_number} - {$ep->latest_update}" . PHP_EOL;
}

echo PHP_EOL;
echo "2. Menambahkan Video Server Baru ke Episode..." . PHP_EOL;
echo "-----------------------------------------------" . PHP_EOL;

// 2. Ambil episode random yang ada
$episode = Episode::whereHas('anime')->inRandomOrder()->first();

if ($episode) {
    echo "Target: {$episode->anime->title} EP{$episode->episode_number}" . PHP_EOL;
    
    // Tambahkan video server baru
    $newServer = VideoServer::create([
        'episode_id' => $episode->id,
        'server_name' => 'NewServer-' . time(),
        'embed_url' => 'https://example.com/new/' . $episode->id,
        'is_active' => true,
    ]);
    
    echo "✓ Video server '{$newServer->server_name}' berhasil ditambahkan!" . PHP_EOL;
    echo PHP_EOL;
    
    // 3. Tampilkan episode terbaru setelah penambahan
    echo "3. Episode Terbaru Setelah Penambahan Video Server:" . PHP_EOL;
    echo "---------------------------------------------------" . PHP_EOL;
    $updated = DB::table('episodes')
        ->join('animes', 'episodes.anime_id', '=', 'animes.id')
        ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
        ->where('video_servers.is_active', true)
        ->select(
            'episodes.id as episode_id',
            'animes.title',
            'episodes.episode_number',
            DB::raw('MAX(video_servers.updated_at) as latest_update')
        )
        ->groupBy('episodes.id', 'animes.title', 'episodes.episode_number')
        ->orderBy('latest_update', 'desc')
        ->limit(5)
        ->get();

    foreach ($updated as $i => $ep) {
        $marker = ($ep->episode_id == $episode->id) ? ' ← BARU!' : '';
        echo ($i + 1) . ". {$ep->title} EP{$ep->episode_number} - {$ep->latest_update}{$marker}" . PHP_EOL;
    }
    
    echo PHP_EOL;
    echo "✓ Episode yang baru ditambahkan video server-nya sekarang muncul di urutan teratas!" . PHP_EOL;
    
} else {
    echo "⚠️  Tidak ada episode yang tersedia untuk demo." . PHP_EOL;
}

echo PHP_EOL;
echo "=== Demo Selesai ===" . PHP_EOL;
