<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\VideoServer;

// Tambah pattern server sync yang terlewat
$syncServerPatterns = [
    'ARCS%',
    'GIDEO%', 
    'KODIR%',
];

$totalUpdated = 0;

foreach ($syncServerPatterns as $pattern) {
    $count = VideoServer::where('server_name', 'like', $pattern)
        ->where('source', '!=', 'sync')
        ->update(['source' => 'sync']);
    
    echo "Updated {$count} servers matching '{$pattern}'\n";
    $totalUpdated += $count;
}

echo "\n===================\n";
echo "Total Updated: {$totalUpdated}\n";

// Cek distribusi sekarang
$manual = VideoServer::where('source', 'manual')->count();
$sync = VideoServer::where('source', 'sync')->count();
echo "\nSekarang:\n";
echo "Source = 'manual': {$manual}\n";
echo "Source = 'sync': {$sync}\n";
