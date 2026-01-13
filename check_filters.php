<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Available Years ===" . PHP_EOL;
$years = DB::table('animes')->distinct()->pluck('release_year')->filter()->sort()->values();
echo $years->toJson() . PHP_EOL;

echo PHP_EOL;
echo "=== Test Genre Bug ===" . PHP_EOL;
echo "Testing search with genre filter..." . PHP_EOL;

// Simulate search with genre
$genre = DB::table('genres')->first();
if ($genre) {
    echo "Testing with Genre: {$genre->name} (ID: {$genre->id})" . PHP_EOL;
    
    $animes = DB::table('animes')
        ->join('anime_genre', 'animes.id', '=', 'anime_genre.anime_id')
        ->where('anime_genre.genre_id', $genre->id)
        ->select('animes.*')
        ->limit(5)
        ->get();
    
    echo "Found " . $animes->count() . " animes" . PHP_EOL;
}
