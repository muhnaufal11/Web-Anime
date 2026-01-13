<?php
// Fix composer.json to use Laravel 11 and Filament v3
$composerPath = __DIR__ . '/composer.json';
$composer = json_decode(file_get_contents($composerPath), true);

// Update requirements
$composer['require']['php'] = '^8.2';
$composer['require']['laravel/framework'] = '^11.0';
$composer['require']['filament/filament'] = '^3.2';

// Add Filament as require
if (!isset($composer['require']['filament/filament'])) {
    $composer['require']['filament/filament'] = '^3.2';
}

file_put_contents($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "✓ composer.json updated for Laravel 11 and Filament v3\n";
?>
