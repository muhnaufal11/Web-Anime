<?php

// Debug AnimeSail scraper

$url = 'https://154.26.137.28/anime/one-piece/';

echo "Testing URL: {$url}\n\n";

// Test with cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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

if (empty($html)) {
    echo "ERROR: Empty response!\n";
    exit(1);
}

// Check for episode list patterns
echo "=== Pattern Detection ===\n";

// Pattern 1: ul.daftar
if (preg_match('/<ul[^>]*class="[^"]*daftar[^"]*"[^>]*>(.*?)<\/ul>/is', $html, $m)) {
    echo "✓ Found ul.daftar\n";
    echo "  Content length: " . strlen($m[1]) . "\n";
    
    // Extract episodes
    preg_match_all('/<li>\s*<a\s+href="([^"]+)"[^>]*>([^<]+)<\/a>/is', $m[1], $eps, PREG_SET_ORDER);
    echo "  Episodes found: " . count($eps) . "\n";
    
    if (count($eps) > 0) {
        echo "  First 3:\n";
        foreach (array_slice($eps, 0, 3) as $ep) {
            echo "    - {$ep[2]}\n";
            echo "      URL: {$ep[1]}\n";
        }
    }
} else {
    echo "✗ ul.daftar NOT found\n";
}

// Pattern 2: Alternative episode links
preg_match_all('/<a[^>]+href="([^"]*episode[^"]*)"[^>]*>([^<]*Episode[^<]*)<\/a>/is', $html, $eps2, PREG_SET_ORDER);
echo "\nAlternative pattern: " . count($eps2) . " episodes\n";

// Pattern 3: Check what classes exist
if (preg_match_all('/class="([^"]+)"/', $html, $classes)) {
    $unique = array_unique($classes[1]);
    $episodeRelated = array_filter($unique, fn($c) => stripos($c, 'episode') !== false || stripos($c, 'list') !== false || stripos($c, 'daftar') !== false);
    echo "\nRelevant classes found: " . implode(', ', array_slice($episodeRelated, 0, 10)) . "\n";
}

// Check title
if (preg_match('/<h1[^>]*class="[^"]*entry-title[^"]*"[^>]*>([^<]+)/i', $html, $title)) {
    echo "\nTitle: {$title[1]}\n";
} else {
    echo "\nTitle NOT found with entry-title\n";
    // Try alternative
    if (preg_match('/<title>([^<]+)/i', $html, $title2)) {
        echo "Page title: {$title2[1]}\n";
    }
}

// Save HTML for inspection
file_put_contents('/tmp/animesail_debug.html', $html);
echo "\nHTML saved to /tmp/animesail_debug.html\n";

// Show a snippet around episode list
$pos = stripos($html, 'Episode');
if ($pos !== false) {
    echo "\n=== HTML around 'Episode' ===\n";
    echo substr($html, max(0, $pos - 200), 500) . "\n";
}
