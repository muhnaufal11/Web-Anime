#!/php
<?php

/**
 * Quick Test untuk Fuzzy Search Feature
 * Jalankan: php artisan tinker < fuzzy_search_test.php
 * atau copy-paste kode di bawah ke artisan tinker
 */

// Test 1: Exact match
echo "=== TEST 1: Exact Match ===\n";
$anime = App\Models\Anime::where('title', 'like', '%Naruto%')->first();
if ($anime) {
    echo "✓ Found: " . $anime->title . "\n";
} else {
    echo "✗ No exact match\n";
}

// Test 2: Case insensitive
echo "\n=== TEST 2: Case Insensitive ===\n";
$search = 'NARUTO';
$matches = App\Models\Anime::whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%'])->get();
echo "✓ Found " . $matches->count() . " matches for '" . $search . "'\n";
if ($matches->count() > 0) {
    echo "  First: " . $matches->first()->title . "\n";
}

// Test 3: Levenshtein distance (typo)
echo "\n=== TEST 3: Levenshtein Distance ===\n";
$search = 'Narto';
$targetTitle = 'Naruto';
$distance = levenshtein(strtolower($search), strtolower($targetTitle));
echo "Distance between '{$search}' and '{$targetTitle}': " . $distance . " (lower = better)\n";
if ($distance <= 3) {
    echo "✓ Acceptable typo tolerance\n";
}

// Test 4: SOUNDEX matching
echo "\n=== TEST 4: SOUNDEX Matching ===\n";
$word1 = 'Shingeki';
$word2 = 'Shingaki';
$soundex1 = soundex($word1);
$soundex2 = soundex($word2);
echo "SOUNDEX('{$word1}'): " . $soundex1 . "\n";
echo "SOUNDEX('{$word2}'): " . $soundex2 . "\n";
if ($soundex1 === $soundex2) {
    echo "✓ SOUNDEX match detected\n";
} else {
    echo "✗ No SOUNDEX match\n";
}

// Test 5: Similar text percentage
echo "\n=== TEST 5: Similar Text Percentage ===\n";
$str1 = 'Naruto';
$str2 = 'Narto';
similar_text(strtolower($str1), strtolower($str2), $percent);
echo "Similarity between '{$str1}' and '{$str2}': " . number_format($percent, 2) . "%\n";
if ($percent >= 50) {
    echo "✓ Good match\n";
}

// Test 6: Database query with fuzzy methods
echo "\n=== TEST 6: Database Fuzzy Query ===\n";
$search = 'dbz';
$results = App\Models\Anime::where(function($q) use ($search) {
    $q->where('title', 'like', '%' . $search . '%')
      ->orWhereRaw('SOUNDEX(title) = SOUNDEX(?)', [$search])
      ->orWhereRaw('LOWER(REPLACE(title, " ", "")) LIKE ?', ['%' . strtolower($search) . '%']);
})->get();
echo "✓ Found " . $results->count() . " matches for '" . $search . "'\n";
$results->each(function($a) { echo "  - " . $a->title . "\n"; });

// Test 7: API endpoint test (via URL)
echo "\n=== TEST 7: API Endpoint ===\n";
echo "Test the API manually:\n";
echo "GET /api/search/suggestions?q=narto\n";
echo "Expected: Should return Naruto and similar titles\n";

echo "\n=== All tests completed! ===\n";
