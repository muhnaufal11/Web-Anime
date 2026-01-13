<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Anime;
use App\Models\User;

echo "=== Check Image Paths ===" . PHP_EOL;

$anime = Anime::first();
if ($anime) {
    echo "Anime: {$anime->title}" . PHP_EOL;
    echo "poster column: " . ($anime->poster ?? 'NULL') . PHP_EOL;
    echo "poster_image column: " . ($anime->poster_image ?? 'NULL') . PHP_EOL;
    
    // Check all columns
    echo PHP_EOL . "All columns:" . PHP_EOL;
    foreach ($anime->getAttributes() as $key => $value) {
        if (str_contains(strtolower($key), 'poster') || str_contains(strtolower($key), 'image')) {
            echo "  {$key}: " . ($value ?? 'NULL') . PHP_EOL;
        }
    }
}

echo PHP_EOL;

$user = User::first();
if ($user) {
    echo "User: {$user->name}" . PHP_EOL;
    echo "Avatar path in DB: " . ($user->avatar ?? 'NULL') . PHP_EOL;
    if ($user->avatar) {
        $fullPath = storage_path('app/public/' . $user->avatar);
        echo "Full path: {$fullPath}" . PHP_EOL;
        echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . PHP_EOL;
    }
}
