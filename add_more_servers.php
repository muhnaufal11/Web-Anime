<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Anime;
use App\Models\Episode;
use App\Models\VideoServer;

echo "=== Menambahkan Video Server ke Anime Lain ===" . PHP_EOL;
echo PHP_EOL;

// Get anime yang belum punya video server
$animesWithoutServers = Anime::whereDoesntHave('episodes.videoServers')
    ->with('episodes')
    ->limit(5)
    ->get();

if ($animesWithoutServers->isEmpty()) {
    echo "Semua anime sudah memiliki video server!" . PHP_EOL;
    
    // Update beberapa video server untuk simulasi update terbaru
    $recentEpisodes = Episode::whereHas('videoServers')
        ->with('anime', 'videoServers')
        ->limit(5)
        ->get();
    
    echo PHP_EOL;
    echo "Mengupdate beberapa video server untuk simulasi..." . PHP_EOL;
    
    foreach ($recentEpisodes as $index => $episode) {
        // Update 1 video server per episode dengan waktu berbeda
        $server = $episode->videoServers->first();
        if ($server) {
            $server->touch(); // Update updated_at ke sekarang
            // Tunggu 1 detik agar timestamp berbeda
            sleep(1);
            echo ($index + 1) . ". Updated: {$episode->anime->title} EP{$episode->episode_number} - Server: {$server->server_name}" . PHP_EOL;
        }
    }
} else {
    foreach ($animesWithoutServers as $index => $anime) {
        $episodes = $anime->episodes->take(2); // Ambil 2 episode pertama
        
        if ($episodes->isEmpty()) {
            echo "⚠️  {$anime->title} tidak punya episode, skip..." . PHP_EOL;
            continue;
        }
        
        foreach ($episodes as $episode) {
            // Tambahkan beberapa video server
            VideoServer::create([
                'episode_id' => $episode->id,
                'server_name' => 'StreamSB',
                'embed_url' => 'https://example.com/streamsb/' . $episode->id,
                'is_active' => true,
            ]);
            
            VideoServer::create([
                'episode_id' => $episode->id,
                'server_name' => 'Doodstream',
                'embed_url' => 'https://example.com/doodstream/' . $episode->id,
                'is_active' => true,
            ]);
            
            // Tunggu 1 detik agar timestamp berbeda
            sleep(1);
            
            echo ($index + 1) . ". Ditambahkan video server ke: {$anime->title} EP{$episode->episode_number}" . PHP_EOL;
        }
    }
}

echo PHP_EOL;
echo "✓ Selesai!" . PHP_EOL;
