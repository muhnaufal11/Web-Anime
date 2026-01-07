<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Episode;

try {
    $episode = Episode::first();
    $html = view('livewire.video-player', [
        'episode' => $episode,
        'selectedServer' => null,
        'selectedServerId' => 0,
        'extractedVideo' => null
    ])->render();
    
    echo "✓ View rendered successfully!\n";
    echo "Length: " . strlen($html) . " bytes\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
