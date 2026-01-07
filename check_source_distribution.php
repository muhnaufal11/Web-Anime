<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\VideoServer;

// Cek distribusi source
$total = VideoServer::count();
$manual = VideoServer::where('source', 'manual')->count();
$sync = VideoServer::where('source', 'sync')->count();
$null = VideoServer::whereNull('source')->count();

echo "Total Video Servers: {$total}\n";
echo "Source = 'manual': {$manual}\n";
echo "Source = 'sync': {$sync}\n";
echo "Source = NULL: {$null}\n";
