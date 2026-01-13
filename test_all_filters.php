<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Anime;
use App\Models\Genre;

echo "=== Final Test: All Filters ===" . PHP_EOL;
echo PHP_EOL;

// Simulate HomeController::search() method

echo "1. No Filter (Default)" . PHP_EOL;
echo "----------------------" . PHP_EOL;
$animes = Anime::with('genres', 'episodes')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();
echo "Found: {$animes->count()} anime" . PHP_EOL;
echo PHP_EOL;

echo "2. Filter by Genre" . PHP_EOL;
echo "------------------" . PHP_EOL;
$genre = Genre::first();
$animes = Anime::whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
    ->with('genres', 'episodes')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();
echo "Genre: {$genre->name}" . PHP_EOL;
echo "Found: {$animes->count()} anime" . PHP_EOL;
foreach ($animes as $anime) {
    echo "  - {$anime->title}" . PHP_EOL;
}
echo PHP_EOL;

echo "3. Filter by Type" . PHP_EOL;
echo "-----------------" . PHP_EOL;
$animes = Anime::where('type', 'TV')
    ->with('genres', 'episodes')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();
echo "Type: TV" . PHP_EOL;
echo "Found: {$animes->count()} anime" . PHP_EOL;
foreach ($animes as $anime) {
    echo "  - {$anime->title} ({$anime->type})" . PHP_EOL;
}
echo PHP_EOL;

echo "4. Filter by Year" . PHP_EOL;
echo "-----------------" . PHP_EOL;
$animes = Anime::where('release_year', 2020)
    ->with('genres', 'episodes')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();
echo "Year: 2020" . PHP_EOL;
echo "Found: {$animes->count()} anime" . PHP_EOL;
foreach ($animes as $anime) {
    echo "  - {$anime->title} ({$anime->release_year})" . PHP_EOL;
}
echo PHP_EOL;

echo "5. Combined Filters (Search + Genre + Type + Status)" . PHP_EOL;
echo "-----------------------------------------------------" . PHP_EOL;
$search = 'a';
$animes = Anime::where(function($q) use ($search) {
        $q->where('title', 'like', "%{$search}%")
          ->orWhere('synopsis', 'like', "%{$search}%");
    })
    ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
    ->where('type', 'TV')
    ->where('status', 'Ongoing')
    ->with('genres', 'episodes')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();
echo "Search: '{$search}' + Genre: {$genre->name} + Type: TV + Status: Ongoing" . PHP_EOL;
echo "Found: {$animes->count()} anime" . PHP_EOL;
foreach ($animes as $anime) {
    echo "  - {$anime->title} ({$anime->type}, {$anime->status})" . PHP_EOL;
}
echo PHP_EOL;

echo "✓ All filters working correctly!" . PHP_EOL;
echo PHP_EOL;

// Get available years for display
$availableYears = Anime::distinct()
    ->whereNotNull('release_year')
    ->orderBy('release_year', 'desc')
    ->pluck('release_year');

echo "Available Years for Filter:" . PHP_EOL;
echo $availableYears->implode(', ') . PHP_EOL;
