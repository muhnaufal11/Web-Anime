<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VideoServer;
use App\Models\Episode;

// Find episode with mp4upload server
$server = VideoServer::where('embed_url', 'like', '%mp4upload%')
    ->where('is_active', true)
    ->first();

if ($server) {
    // Clear other defaults for this episode
    VideoServer::where('episode_id', $server->episode_id)
        ->update(['is_default' => false]);
    
    // Set mp4upload as default
    $server->is_default = true;
    $server->save();
    
    $episode = Episode::find($server->episode_id);
    
    echo "✓ Set mp4upload server as default\n";
    echo "Episode: {$episode->slug}\n";
    echo "Server: {$server->server_name}\n";
    echo "\nTest URL: https://nipnime.my.id/watch/{$episode->slug}\n";
} else {
    echo "No active mp4upload server found\n";
}
