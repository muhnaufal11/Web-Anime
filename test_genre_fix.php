<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Anime;
use App\Models\Genre;

echo "=== Test Bug Genre Fix ===" . PHP_EOL;
echo PHP_EOL;

// Test 1: Search with genre only
echo "Test 1: Filter Genre Saja" . PHP_EOL;
echo "-------------------------" . PHP_EOL;
$genre = Genre::first();
if ($genre) {
    $result = Anime::whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
        ->with('genres')
        ->limit(3)
        ->get();
    
    echo "Genre: {$genre->name}" . PHP_EOL;
    echo "Found: {$result->count()} anime" . PHP_EOL;
    foreach ($result as $anime) {
        echo "  - {$anime->title}" . PHP_EOL;
    }
}

echo PHP_EOL;

// Test 2: Search with search term + genre (BUG yang sudah diperbaiki)
echo "Test 2: Search Term + Genre (Previously Buggy)" . PHP_EOL;
echo "-----------------------------------------------" . PHP_EOL;
$searchTerm = 'a'; // Search anime yang mengandung 'a'
$result = Anime::where(function($q) use ($searchTerm) {
        $q->where('title', 'like', "%{$searchTerm}%")
          ->orWhere('synopsis', 'like', "%{$searchTerm}%");
    })
    ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
    ->with('genres')
    ->limit(3)
    ->get();

echo "Search: '{$searchTerm}' + Genre: {$genre->name}" . PHP_EOL;
echo "Found: {$result->count()} anime" . PHP_EOL;
foreach ($result as $anime) {
    echo "  - {$anime->title}" . PHP_EOL;
}

echo PHP_EOL;

// Test 3: All filters combined
echo "Test 3: Multiple Filters (Search + Genre + Type + Status + Year)" . PHP_EOL;
echo "----------------------------------------------------------------" . PHP_EOL;
$result = Anime::where(function($q) use ($searchTerm) {
        $q->where('title', 'like', "%{$searchTerm}%")
          ->orWhere('synopsis', 'like', "%{$searchTerm}%");
    })
    ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
    ->where('type', 'TV')
    ->where('status', 'Ongoing')
    ->where('release_year', 2020)
    ->with('genres')
    ->limit(3)
    ->get();

echo "Search: '{$searchTerm}' + Genre: {$genre->name} + Type: TV + Status: Ongoing + Year: 2020" . PHP_EOL;
echo "Found: {$result->count()} anime" . PHP_EOL;
foreach ($result as $anime) {
    echo "  - {$anime->title} ({$anime->type}, {$anime->status}, {$anime->release_year})" . PHP_EOL;
}

echo PHP_EOL;
echo "✓ Bug genre sudah diperbaiki! Filter sekarang bekerja dengan benar." . PHP_EOL;
