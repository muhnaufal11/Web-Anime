<?php

// Test Video Extraction untuk diagnosa masalah

require_once '/app/vendor/autoload.php';

$app = require_once '/app/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\VideoExtractor\VideoExtractorService;
use App\Services\VideoExtractor\Mp4UploadExtractor;
use Illuminate\Support\Facades\Http;

// Test URL mp4upload
$testUrls = [
    'https://www.mp4upload.com/embed-xb3psmvw2z87.html',
    'https://www.mp4upload.com/embed-cteymxx793il.html',
];

echo "=== VIDEO EXTRACTION DIAGNOSTIC ===\n\n";

foreach ($testUrls as $url) {
    echo "Testing: $url\n";
    echo str_repeat('-', 60) . "\n";
    
    // Step 1: Check if supported
    $canExtract = VideoExtractorService::canExtract($url);
    echo "1. Can Extract: " . ($canExtract ? 'YES' : 'NO') . "\n";
    
    if (!$canExtract) {
        echo "   SKIP - Not supported\n\n";
        continue;
    }
    
    // Step 2: Try extraction
    echo "2. Extracting...\n";
    $result = VideoExtractorService::extract($url);
    
    if (!$result) {
        echo "   FAILED - No result returned\n\n";
        continue;
    }
    
    echo "   SUCCESS!\n";
    echo "   Host: " . ($result['host'] ?? 'unknown') . "\n";
    echo "   Type: " . ($result['type'] ?? 'unknown') . "\n";
    echo "   URL: " . substr($result['url'] ?? '', 0, 80) . "...\n";
    
    // Step 3: Test if video URL is accessible
    $videoUrl = $result['url'];
    echo "\n3. Testing video URL accessibility...\n";
    
    // Determine proper referer
    $referer = 'https://www.mp4upload.com/';
    
    try {
        $headResponse = Http::withoutVerifying()
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => $referer,
                'Origin' => $referer,
            ])
            ->timeout(15)
            ->head($videoUrl);
        
        echo "   HEAD Status: " . $headResponse->status() . "\n";
        echo "   Content-Type: " . ($headResponse->header('Content-Type') ?? 'none') . "\n";
        echo "   Content-Length: " . ($headResponse->header('Content-Length') ?? 'none') . "\n";
        echo "   Accept-Ranges: " . ($headResponse->header('Accept-Ranges') ?? 'none') . "\n";
        
        if (!$headResponse->successful()) {
            // Try GET with range
            echo "\n   HEAD failed, trying GET with Range...\n";
            $getResponse = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer' => $referer,
                    'Origin' => $referer,
                    'Range' => 'bytes=0-1024',
                ])
                ->timeout(15)
                ->get($videoUrl);
            
            echo "   GET Status: " . $getResponse->status() . "\n";
            echo "   Content-Type: " . ($getResponse->header('Content-Type') ?? 'none') . "\n";
            
            if ($getResponse->successful() || $getResponse->status() === 206) {
                $body = $getResponse->body();
                echo "   Body size: " . strlen($body) . " bytes\n";
                echo "   First bytes (hex): " . bin2hex(substr($body, 0, 16)) . "\n";
                
                // Check if it's actual video data (MP4 starts with ftyp)
                if (str_contains($body, 'ftyp') || str_contains($body, 'moov')) {
                    echo "   ✅ VALID MP4 DATA DETECTED\n";
                } else {
                    echo "   ⚠️ Not MP4 data - might be error page or redirect\n";
                    echo "   Content preview: " . substr($body, 0, 200) . "\n";
                }
            }
        } else {
            echo "   ✅ HEAD successful - video accessible\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== DIAGNOSTIC COMPLETE ===\n";
