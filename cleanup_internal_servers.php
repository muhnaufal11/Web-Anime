<?php

/**
 * Cleanup internal AnimeSail servers that won't work externally
 * Run: php cleanup_internal_servers.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VideoServer;
use Illuminate\Support\Facades\DB;

echo "=== Cleanup Internal AnimeSail Servers ===\n\n";

// Patterns that indicate internal/proxy servers
$patterns = [
    '%154.26.137.28%',      // AnimeSail IP
    '%185.217.95.%',        // Other AnimeSail IPs  
    '%nontonanimeid%/proxy%',
    '%animesail%/proxy%',
    '%/embed-local/%',
];

$totalDeleted = 0;

foreach ($patterns as $pattern) {
    $count = VideoServer::where('embed_url', 'like', $pattern)->count();
    
    if ($count > 0) {
        echo "Pattern: {$pattern}\n";
        echo "  Found: {$count} servers\n";
        
        // Show sample
        $samples = VideoServer::where('embed_url', 'like', $pattern)
            ->select('id', 'server_name', 'embed_url')
            ->limit(3)
            ->get();
            
        foreach ($samples as $s) {
            $url = substr($s->embed_url, 0, 80);
            echo "    - [{$s->id}] {$s->server_name}: {$url}...\n";
        }
        
        // Delete
        $deleted = VideoServer::where('embed_url', 'like', $pattern)->delete();
        echo "  Deleted: {$deleted}\n\n";
        
        $totalDeleted += $deleted;
    }
}

echo "=== Summary ===\n";
echo "Total deleted: {$totalDeleted} servers\n";
echo "Done!\n";
