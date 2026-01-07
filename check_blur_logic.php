<?php
/**
 * Check blur logic for specific anime
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get first hentai anime
$anime = App\Models\Anime::whereHas('genres', function($q) {
    $q->where('slug', 'hentai');
})->first();

if (!$anime) {
    echo "No hentai anime found!\n";
    exit(1);
}

echo "=== Testing anime: {$anime->title} ===\n";
echo "Slug: {$anime->slug}\n";
echo "Genres: " . $anime->genres->pluck('name')->join(', ') . "\n";

echo "\n=== Without Auth (guest) ===\n";
auth()->logout(); // Make sure logged out
echo "auth()->check(): " . (auth()->check() ? 'YES' : 'NO') . "\n";
echo "isAdultContent(): " . ($anime->isAdultContent() ? 'YES' : 'NO') . "\n";
echo "canUserView(): " . ($anime->canUserView() ? 'YES' : 'NO') . "\n";
echo "shouldBlurPoster(): " . ($anime->shouldBlurPoster() ? 'YES' : 'NO') . "\n";

echo "\n=== Check the actual view rendering ===\n";
// Simulate what happens in the view
$shouldBlurDetail = $anime->shouldBlurPoster();
$isAdultContent = $anime->isAdultContent();
echo "In view - shouldBlurDetail: " . ($shouldBlurDetail ? 'YES (blur applied)' : 'NO (no blur)') . "\n";
echo "In view - isAdultContent: " . ($isAdultContent ? 'YES' : 'NO') . "\n";

// Check current logged in user if any
$user = auth()->user();
if ($user) {
    echo "\n=== Current user check ===\n";
    echo "User: {$user->email}\n";
    echo "Birthday: " . ($user->birthday ?? 'NOT SET') . "\n";
    echo "isAdult(): " . ($user->isAdult() ? 'YES' : 'NO') . "\n";
}

echo "\n=== URL to test ===\n";
echo "Visit: /detail/{$anime->slug} or /anime/{$anime->slug}\n";
