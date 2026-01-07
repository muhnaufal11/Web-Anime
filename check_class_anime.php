<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Anime;

$anime = Anime::where('title', 'like', '%Class no Daikirai%')->first();

echo "Title: {$anime->title}\n";
echo "ID: {$anime->id}\n\n";

$episodes = $anime->episodes()->orderBy('episode_number')->get();
echo "Total Episodes: " . $episodes->count() . "\n\n";

foreach ($episodes as $ep) {
    $servers = $ep->videoServers;
    $manualServers = $servers->where('source', 'manual');
    $syncServers = $servers->where('source', 'sync');
    
    echo "Episode {$ep->episode_number}:\n";
    echo "  - Total Servers: " . $servers->count() . "\n";
    echo "  - Manual: " . $manualServers->count() . " (" . $manualServers->pluck('server_name')->implode(', ') . ")\n";
    echo "  - Sync: " . $syncServers->count() . "\n\n";
}
