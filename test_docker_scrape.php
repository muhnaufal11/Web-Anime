<?php

// Test script to run in docker

require_once '/app/vendor/autoload.php';

$app = require_once '/app/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$scraper = new \App\Services\AnimeSailScraper();

$url = 'https://154.26.137.28/mikata-ga-yowasugite-hojo-mahou-ni-tesshiteita-kyuutei-mahoushi-tsuihou-sarete-saikyou-wo-mezashimasu-episode-12/';

echo "Testing: $url\n\n";

$result = $scraper->fetchEpisodeServers($url);

echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
echo "Server Count: " . ($result['count'] ?? 0) . "\n\n";

if (!empty($result['servers'])) {
    echo "First 10 servers:\n";
    foreach (array_slice($result['servers'], 0, 10) as $s) {
        echo "  - {$s['name']}: {$s['url']}\n";
    }
} else {
    echo "No servers found!\n";
    if (!empty($result['error'])) {
        echo "Error: {$result['error']}\n";
    }
}
