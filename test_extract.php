<?php

// Test extraction dari episode HTML

$html = file_get_contents('C:\\Users\\naufa\\Downloads\\bahan web\\Mikata ga Yowasugite Hojo Mahou ni Tesshiteita Kyuutei Mahoushi, Tsuihou sarete Saikyou wo Mezashimasu Episode 12 – AnimeSail.html');

echo "HTML Length: " . strlen($html) . " bytes\n\n";

$servers = [];

// Helper function to check internal server
function isInternalServerUrl($url) {
    $patterns = ['154.26.137.28', '185.217.95.', 'nontonanimeid', 'animesail', '/proxy/', '/embed-local/', 'aghanim.xyz'];
    foreach ($patterns as $pattern) {
        if (stripos($url, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

// METHOD 1: Extract from data-default attribute (base64 encoded iframe)
echo "=== Method 1: data-default ===\n";
if (preg_match('/data-default="([^"]+)"/i', $html, $defaultMatch)) {
    $decoded = base64_decode($defaultMatch[1]);
    if (preg_match('/src="([^"]+)"/i', $decoded, $srcMatch)) {
        $url = $srcMatch[1];
        if (!isInternalServerUrl($url)) {
            echo "✓ Default: {$url}\n";
            $servers[] = ['name' => 'Default', 'url' => $url];
        } else {
            echo "✗ Default (internal): {$url}\n";
        }
    }
}

// METHOD 2: Extract from select.mirror options with data-em attribute
echo "\n=== Method 2: select options (data-em) ===\n";
preg_match_all('/<option[^>]*data-em="([^"]+)"[^>]*>([^<]+)<\/option>/i', $html, $optionMatches, PREG_SET_ORDER);
echo "Found " . count($optionMatches) . " options with data-em\n";

$validCount = 0;
$skippedCount = 0;
foreach ($optionMatches as $opt) {
    $decoded = base64_decode($opt[1], true);
    if ($decoded && preg_match('/src="([^"]+)"/i', $decoded, $srcMatch)) {
        $url = $srcMatch[1];
        $name = trim($opt[2]);
        if (!isInternalServerUrl($url)) {
            $validCount++;
            // Check duplicate
            $exists = false;
            foreach ($servers as $s) {
                if ($s['url'] === $url) { $exists = true; break; }
            }
            if (!$exists) {
                $servers[] = ['name' => $name, 'url' => $url];
                if ($validCount <= 10) {
                    echo "  ✓ {$name}: " . substr($url, 0, 60) . "\n";
                }
            }
        } else {
            $skippedCount++;
        }
    }
}
echo "Valid external: {$validCount}, Skipped internal: {$skippedCount}\n";

echo "\n=== Total valid servers: " . count($servers) . " ===\n";
foreach (array_slice($servers, 0, 15) as $s) {
    echo "  - {$s['name']}: {$s['url']}\n";
}
if (count($servers) > 15) {
    echo "  ... and " . (count($servers) - 15) . " more\n";
}
