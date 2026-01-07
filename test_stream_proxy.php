<?php

// Test Streaming Proxy untuk diagnosa

require_once '/app/vendor/autoload.php';

$app = require_once '/app/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

// Test URL yang sudah diextract
$videoUrl = 'https://a4.mp4upload.com:183/d/xkx7njvez3b4quuororqematkheqb6tiln3sf6wfocnm7tf27rxwjohhlbh2aasxlycmfxp4fegaizsa/video.mp4';

echo "=== STREAMING PROXY DIAGNOSTIC ===\n\n";

echo "Original Video URL:\n$videoUrl\n\n";

// Test curl directly dari Docker
echo "1. Testing CURL from Docker to video URL...\n";

$ch = curl_init($videoUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => true, // HEAD request
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Referer: https://www.mp4upload.com/',
        'Origin: https://www.mp4upload.com',
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Content-Type: $contentType\n";
echo "   Content-Length: $contentLength bytes\n";
echo "   Effective URL: $effectiveUrl\n";
if ($error) echo "   CURL Error: $error\n";
echo "\n";

// Test dengan Range request (seperti browser)
echo "2. Testing Range request (bytes=0-1048576)...\n";

$ch = curl_init($videoUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_RANGE => '0-1048576', // First 1MB
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Referer: https://www.mp4upload.com/',
        'Origin: https://www.mp4upload.com',
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);
$headers = substr($response, 0, $headerSize);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Body size: " . strlen($body) . " bytes\n";

// Parse Content-Range header
if (preg_match('/Content-Range:\s*bytes\s+(\d+)-(\d+)\/(\d+)/i', $headers, $m)) {
    echo "   Content-Range: bytes {$m[1]}-{$m[2]}/{$m[3]}\n";
}

// Check if valid MP4
$hexStart = bin2hex(substr($body, 0, 8));
echo "   First 8 bytes (hex): $hexStart\n";

if (strpos($body, 'ftyp') !== false) {
    echo "   ✅ Valid MP4 header detected (ftyp)\n";
} elseif (strpos($body, 'mdat') !== false || strpos($body, 'moov') !== false) {
    echo "   ✅ Valid MP4 atoms detected\n";
} else {
    echo "   ⚠️ No MP4 signature - checking content...\n";
    // Show first 500 chars if it's text/error
    if (strlen($body) < 1000 || strpos($body, '<') === 0) {
        echo "   Content: " . substr($body, 0, 500) . "\n";
    }
}

echo "\n3. Test CORS simulation...\n";
echo "   The streaming proxy should add these headers:\n";
echo "   - Access-Control-Allow-Origin: *\n";
echo "   - Access-Control-Allow-Methods: GET, HEAD, OPTIONS\n";
echo "   - Content-Type: video/mp4\n";

echo "\n=== DIAGNOSTIC COMPLETE ===\n";

// Check cached extraction
echo "\n4. Checking cache...\n";
$cacheKey = 'mp4upload_' . md5('https://www.mp4upload.com/embed-xb3psmvw2z87.html');
$cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
if ($cached) {
    echo "   Cache found for this URL\n";
    echo "   Cached URL: " . substr($cached['url'] ?? '', 0, 80) . "...\n";
} else {
    echo "   No cache found\n";
}
