<?php
/**
 * Delete invalid video servers with placeholder URLs
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Delete servers with nontonanimeid:// placeholder URLs (including in iframe)
$deleted = App\Models\VideoServer::where('embed_url', 'LIKE', '%nontonanimeid://%')->delete();
echo "Deleted {$deleted} servers with nontonanimeid:// placeholder URLs\n";

// Delete servers with s2.kotakanimeid.link/out/ (redirect URLs, not embeddable)  
$deleted2 = App\Models\VideoServer::where('embed_url', 'LIKE', '%kotakanimeid.link/out/%')->delete();
echo "Deleted {$deleted2} servers with redirect/download URLs\n";

// Show remaining servers for Ao no Orchestra Season 2 Episode 13
$episode = App\Models\Episode::whereHas('anime', function($q) {
    $q->where('title', 'LIKE', '%Ao no Orchestra Season 2%');
})->where('episode_number', 13)->first();

if ($episode) {
    echo "\nRemaining servers for {$episode->anime->title} Ep {$episode->episode_number}:\n";
    foreach ($episode->videoServers as $server) {
        echo "- {$server->server_name}: " . substr($server->embed_url, 0, 100) . "...\n";
    }
    if ($episode->videoServers->isEmpty()) {
        echo "No servers remaining.\n";
    }
} else {
    echo "\nEpisode not found\n";
}
