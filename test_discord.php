<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\DiscordNotificationService;
use App\Models\Episode;

$discord = new DiscordNotificationService();

// Get latest episode that has video servers
$episode = Episode::with(['anime', 'creator', 'anime.genres'])
    ->whereHas('videoServers')
    ->latest()
    ->first();

if ($episode) {
    echo "Sending notification for: {$episode->anime->title} Episode {$episode->episode_number}\n";
    echo "Creator: " . ($episode->creator->name ?? 'Unknown') . "\n";
    echo "Video servers count: " . $episode->videoServers()->count() . "\n";
    $result = $discord->notifyNewEpisode($episode);
    echo $result ? "✅ Notification sent successfully!\n" : "❌ Failed to send notification\n";
} else {
    echo "No episode with video servers found\n";
}
