<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\VideoServer;

// Reset semua ke sync dulu
$resetCount = VideoServer::where('source', 'manual')->update(['source' => 'sync']);
echo "Reset {$resetCount} servers to 'sync'\n\n";

// Set manual HANYA untuk SERVER ADMIN
$manualCount = VideoServer::where('server_name', 'like', '%SERVER ADMIN%')
    ->update(['source' => 'manual']);

echo "Set {$manualCount} servers to 'manual' (IP address / SERVER ADMIN / DEFAULT)\n\n";

// Cek distribusi sekarang
$manual = VideoServer::where('source', 'manual')->count();
$sync = VideoServer::where('source', 'sync')->count();
echo "Sekarang:\n";
echo "Source = 'manual': {$manual}\n";
echo "Source = 'sync': {$sync}\n";

// Sample manual servers
echo "\nSample Manual Servers:\n";
$samples = VideoServer::where('source', 'manual')->take(10)->pluck('server_name');
foreach ($samples as $s) {
    echo "  - {$s}\n";
}
