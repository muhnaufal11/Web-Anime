<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Anime;
use App\Models\VideoServer;

// Cari anime yang:
// - Punya episode dengan sync server
// - Tapi ada episode tanpa manual server
$animes = Anime::where('release_year', 2025)
    ->whereHas('episodes.videoServers', function($q) {
        $q->where('source', 'sync'); // punya sync
    })
    ->whereHas('episodes', function($q) {
        $q->whereDoesntHave('videoServers', function($vs) {
            $vs->where('source', 'manual'); // tidak punya manual
        });
    })
    ->withCount([
        'episodes',
        'episodes as episodes_with_sync' => function ($query) {
            $query->whereHas('videoServers', function($q) {
                $q->where('source', 'sync');
            });
        },
        'episodes as episodes_with_manual' => function ($query) {
            $query->whereHas('videoServers', function($q) {
                $q->where('source', 'manual');
            });
        }
    ])
    ->take(20)
    ->get();

echo "Anime 2025 yang punya Sync tapi butuh Manual:\n";
echo "==============================================\n";
foreach ($animes as $anime) {
    echo "{$anime->title}\n";
    echo "  - Total Episodes: {$anime->episodes_count}\n";
    echo "  - Episodes with Sync: {$anime->episodes_with_sync}\n";
    echo "  - Episodes with Manual: {$anime->episodes_with_manual}\n\n";
}

if ($animes->isEmpty()) {
    echo "Tidak ada anime yang punya sync tapi tanpa manual.\n";
}
