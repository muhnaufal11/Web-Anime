<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VideoServer;
use App\Models\Episode;
use App\Services\VideoExtractor\VideoExtractorService;
use App\Services\VideoExtractor\Mp4UploadExtractor;

echo "=== MP4Upload Extractor Test ===\n\n";

// Find episode with mp4upload server and get watch URL
$server = VideoServer::where('embed_url', 'like', '%mp4upload%')
    ->where('is_active', true)
    ->first();

if ($server) {
    $episode = Episode::with('anime')->find($server->episode_id);
    if ($episode) {
        echo "Watch URL: /watch/{$episode->slug}\n";
        echo "Episode: {$episode->title}\n";
        echo "Anime: {$episode->anime->title}\n\n";
    }
}

// Check for mp4upload servers
$mp4uploadServers = VideoServer::where('embed_url', 'like', '%mp4upload%')->get();
echo "Found {$mp4uploadServers->count()} mp4upload servers in database\n\n";

if ($mp4uploadServers->count() > 0) {
    foreach ($mp4uploadServers->take(2) as $server) {
        echo "Server ID: {$server->id}\n";
        echo "Episode ID: {$server->episode_id}\n";
        echo "Embed URL: {$server->embed_url}\n";
        
        $canExtract = VideoExtractorService::canExtract($server->embed_url);
        echo "Can Extract: " . ($canExtract ? 'Yes' : 'No') . "\n";
        
        if ($canExtract) {
            echo "Attempting extraction...\n";
            $result = VideoExtractorService::extract($server->embed_url);
            if ($result) {
                echo "✓ SUCCESS!\n";
                echo "Direct URL: {$result['url']}\n";
                echo "Type: {$result['type']}\n";
                echo "Host: {$result['host']}\n";
            } else {
                echo "✗ Failed to extract\n";
            }
        }
        
        echo "\n" . str_repeat('-', 50) . "\n\n";
    }
}

echo "\nDone!\n";
