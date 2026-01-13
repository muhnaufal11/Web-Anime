<?php
/**
 * Quick check genres in database
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== All Genres ===\n";
$genres = App\Models\Genre::all(['id', 'name', 'slug']);
foreach ($genres as $genre) {
    echo sprintf("ID: %d | Name: %s | Slug: %s\n", $genre->id, $genre->name, $genre->slug);
}

echo "\n=== Looking for adult genres ===\n";
$adultGenres = ['hentai'];
$foundAdult = App\Models\Genre::whereIn('slug', $adultGenres)->get();
echo "Found adult genres: " . ($foundAdult->count() ?: 'NONE') . "\n";
foreach ($foundAdult as $g) {
    echo "- {$g->name} (slug: {$g->slug})\n";
}

echo "\n=== Anime with hentai genre ===\n";
$hentaiAnimes = App\Models\Anime::whereHas('genres', function($q) {
    $q->whereIn('slug', ['hentai']);
})->get(['id', 'title', 'slug']);

if ($hentaiAnimes->isEmpty()) {
    echo "No anime found with hentai genre!\n";
} else {
    foreach ($hentaiAnimes as $anime) {
        echo "- {$anime->title}\n";
    }
}

echo "\n=== Testing shouldBlurPoster on one anime ===\n";
$testAnime = App\Models\Anime::first();
if ($testAnime) {
    echo "Anime: {$testAnime->title}\n";
    echo "Genres: " . $testAnime->genres->pluck('name')->join(', ') . "\n";
    echo "isAdultContent: " . ($testAnime->isAdultContent() ? 'YES' : 'NO') . "\n";
    echo "canUserView: " . ($testAnime->canUserView() ? 'YES' : 'NO') . "\n";
    echo "shouldBlurPoster: " . ($testAnime->shouldBlurPoster() ? 'YES' : 'NO') . "\n";
}

// Check the specific anime from screenshot (looks like episode list)
echo "\n=== Checking anime with adult-looking episodes ===\n";
// Find anime that have 'Hentai' in genre name (case insensitive)
$hentaiByName = App\Models\Genre::where('name', 'LIKE', '%hentai%')->orWhere('name', 'LIKE', '%Hentai%')->first();
if ($hentaiByName) {
    echo "Found hentai genre: ID={$hentaiByName->id}, Name={$hentaiByName->name}, Slug={$hentaiByName->slug}\n";
} else {
    echo "No genre with 'hentai' in name found!\n";
}
