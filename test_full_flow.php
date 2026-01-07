<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VideoServer;
use App\Models\Episode;
use App\Services\VideoExtractor\VideoExtractorService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;

echo "=== Full Video Player Flow Test ===\n\n";

// Find the mp4upload default server
$server = VideoServer::where('embed_url', 'like', '%mp4upload%')
    ->where('is_active', true)
    ->where('is_default', true)
    ->first();

if (!$server) {
    echo "No mp4upload default server found\n";
    exit(1);
}

$episode = Episode::find($server->episode_id);

echo "Episode: {$episode->slug}\n";
echo "Server: {$server->server_name}\n";
echo "Embed URL: " . substr($server->embed_url, 0, 100) . "...\n\n";

// Simulate what VideoPlayer does
$embedUrl = $server->embed_url;

// Extract URL from iframe if needed
if (stripos($embedUrl, '<iframe') !== false) {
    if (preg_match('/src=["\']([^"\']+)["\']/i', $embedUrl, $matches)) {
        $embedUrl = html_entity_decode($matches[1]);
    }
}

echo "Extracted iframe src: {$embedUrl}\n\n";

// Try extraction
if (VideoExtractorService::canExtract($embedUrl)) {
    echo "✓ URL is extractable\n";
    $extractedVideo = VideoExtractorService::extract($embedUrl);
    
    if ($extractedVideo && !empty($extractedVideo['url'])) {
        echo "✓ Extraction successful!\n";
        echo "Direct URL: {$extractedVideo['url']}\n";
        echo "Type: {$extractedVideo['type']}\n";
        echo "Host: {$extractedVideo['host']}\n\n";
        
        // Generate signed proxy URL
        $signedUrl = URL::temporarySignedRoute(
            'stream.extracted',
            now()->addMinutes(60),
            ['token' => Crypt::encryptString($extractedVideo['url'])]
        );
        
        echo "Signed Proxy URL:\n{$signedUrl}\n\n";
        
        // Test the signed URL validity
        echo "Proxy URL domain: " . parse_url($signedUrl, PHP_URL_HOST) . "\n";
        
    } else {
        echo "✗ Extraction returned null\n";
    }
} else {
    echo "✗ URL is not extractable\n";
}

echo "\nDone!\n";
