<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VideoServer;
use App\Services\VideoEmbedHelper;

// Get Server Admin videos
$servers = VideoServer::where('server_name', 'like', '%Admin%')->latest()->take(1)->get();

echo "=== SERVER ADMIN VIDEO DEBUG ===\n\n";
foreach ($servers as $s) {
    echo "ID: {$s->id}\n";
    echo "Episode ID: {$s->episode_id}\n";
    echo "Server: {$s->server_name}\n";
    echo "RAW URL: {$s->embed_url}\n";
    
    // Check detections
    $url = $s->embed_url;
    echo "Contains <iframe>: " . (str_contains($url, '<iframe') ? 'YES' : 'NO') . "\n";
    echo "Ends with .mp4: " . (str_ends_with(strtolower($url), '.mp4') ? 'YES' : 'NO') . "\n";
    echo "Ends with .m3u8: " . (str_ends_with(strtolower($url), '.m3u8') ? 'YES' : 'NO') . "\n";
    
    // Check proxify output
    $proxified = VideoEmbedHelper::proxify($url);
    echo "Proxified: " . ($proxified ?? 'NULL') . "\n";
    
    echo "---\n";
}
