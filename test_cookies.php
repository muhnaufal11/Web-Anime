<?php

// Test AnimeSail with cookies

$url = 'https://154.26.137.28/anime/one-piece/';

echo "Testing URL: {$url}\n\n";

// Required cookies
$cookies = "_as_ipin_ct=ID; _as_ipin_tz=Asia/Jakarta; _as_ipin_lc=id-ID";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_COOKIE, $cookies);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'Accept-Language: en-US,en;q=0.9,id;q=0.8',
]);

$html = curl_exec($ch);
$error = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$code}\n";
echo "cURL Error: {$error}\n";
echo "HTML Length: " . strlen($html) . " bytes\n\n";

// Check if we got real page
if (str_contains($html, '<title>Loading..</title>')) {
    echo "❌ Still got loading page!\n";
} else {
    echo "✓ Got real page!\n";
}

// Check for episode list
if (preg_match('/<ul[^>]*class="[^"]*daftar[^"]*"[^>]*>(.*?)<\/ul>/is', $html, $m)) {
    echo "✓ Found ul.daftar\n";
    
    preg_match_all('/<li>\s*<a\s+href="([^"]+)"[^>]*>([^<]+)<\/a>/is', $m[1], $eps, PREG_SET_ORDER);
    echo "Episodes found: " . count($eps) . "\n\n";
    
    echo "First 5 episodes:\n";
    foreach (array_slice($eps, 0, 5) as $ep) {
        echo "  - {$ep[2]}\n";
    }
} else {
    echo "✗ ul.daftar NOT found\n";
    
    // Check page title
    if (preg_match('/<title>([^<]+)/i', $html, $title)) {
        echo "Page title: {$title[1]}\n";
    }
}
