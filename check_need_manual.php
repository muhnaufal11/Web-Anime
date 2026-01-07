<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Anime;

// Ambil anime 2025 yang punya episode tapi ada episode tanpa manual server
$animes = Anime::where('release_year', 2025)
    ->whereHas('episodes') // punya episode
    ->whereHas('episodes', function($q) {
        // Episode tanpa manual server
        $q->whereDoesntHave('videoServers', function($vs) {
            $vs->where('source', 'manual');
        });
    })
    ->withCount([
        'episodes',
        'episodes as episodes_no_manual' => function ($query) {
            $query->whereDoesntHave('videoServers', function($q) {
                $q->where('source', 'manual');
            });
        }
    ])
    ->orderByDesc('episodes_no_manual')
    ->take(20)
    ->get();

echo "Anime 2025 yang butuh Manual Server:\n";
echo "=====================================\n";
foreach ($animes as $anime) {
    echo "{$anime->title} - {$anime->episodes_no_manual}/{$anime->episodes_count} ep butuh manual\n";
}
