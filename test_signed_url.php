<?php

// Test generate signed URL dan akses

require_once '/app/vendor/autoload.php';

$app = require_once '/app/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

// Video URL yang sudah diextract
$extractedUrl = 'https://a4.mp4upload.com:183/d/xkx7njvez3b4quuororqematkheqb6tiln3sf6wfocnm7tf27rxwjohhlbh2aasxlycmfxp4fegaizsa/video.mp4';

echo "=== SIGNED URL TEST ===\n\n";

// Generate signed URL
$token = Crypt::encryptString($extractedUrl);
$signedUrl = URL::temporarySignedRoute(
    'stream.extracted',
    now()->addMinutes(60),
    ['token' => $token]
);

echo "Extracted URL: " . substr($extractedUrl, 0, 80) . "...\n\n";
echo "Signed URL:\n$signedUrl\n\n";

// Test signature validity
echo "Testing signature...\n";
$parsed = parse_url($signedUrl);
parse_str($parsed['query'] ?? '', $queryParams);

echo "  Signature present: " . (isset($queryParams['signature']) ? 'YES' : 'NO') . "\n";
echo "  Expires present: " . (isset($queryParams['expires']) ? 'YES' : 'NO') . "\n";

if (isset($queryParams['expires'])) {
    $expiresAt = \Carbon\Carbon::createFromTimestamp($queryParams['expires']);
    echo "  Expires at: " . $expiresAt->format('Y-m-d H:i:s') . "\n";
    echo "  Time until expiry: " . now()->diffForHumans($expiresAt, true) . "\n";
}

// Parse token back
echo "\nDecrypting token...\n";
try {
    $decrypted = Crypt::decryptString($token);
    echo "  Decrypted URL: " . substr($decrypted, 0, 80) . "...\n";
    echo "  ✅ Token decryption works\n";
} catch (\Exception $e) {
    echo "  ❌ Token decryption failed: " . $e->getMessage() . "\n";
}

// Test internal access to signed URL (from server itself)
echo "\nTesting signed URL access from server...\n";
$internalUrl = str_replace('https://nipnime.my.id', 'http://localhost', $signedUrl);

$ch = curl_init($internalUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => true, // HEAD
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'Host: nipnime.my.id',
        'User-Agent: Mozilla/5.0',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  Internal test HTTP: $httpCode\n";

// Show URL for manual testing
echo "\n=== MANUAL TEST ===\n";
echo "Copy this URL and open in browser to test:\n";
echo $signedUrl . "\n";
