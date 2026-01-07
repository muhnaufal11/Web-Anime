<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Anime;
use App\Models\VideoServer;

// Cari Wind Breaker
$animes = Anime::where('title', 'like', '%Wind Breaker%')->get();

foreach ($animes as $anime) {
    echo "ID: {$anime->id}\n";
    echo "Title: {$anime->title}\n";
    echo "Year: {$anime->release_year}\n";
    
    $episodes = $anime->episodes;
    echo "Total Episodes: " . $episodes->count() . "\n";
    
    $manualCount = 0;
    $syncCount = 0;
    $noServerCount = 0;
    
    foreach ($episodes as $ep) {
        $servers = $ep->videoServers;
        $hasManual = $servers->where('source', 'manual')->count() > 0;
        $hasSync = $servers->where('source', 'sync')->count() > 0;
        
        if ($hasManual) $manualCount++;
        if ($hasSync) $syncCount++;
        if ($servers->count() == 0) $noServerCount++;
    }
    
    echo "Episodes with Manual Server: {$manualCount}\n";
    echo "Episodes with Sync Server: {$syncCount}\n";
    echo "Episodes without any Server: {$noServerCount}\n";
    echo "---\n";
}
