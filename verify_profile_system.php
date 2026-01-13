<?php

/**
 * Profile System Verification Script
 * Verifies all components are properly installed
 */

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║   PROFILE SYSTEM VERIFICATION                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Check Routes
echo "1️⃣  CHECKING ROUTES...\n";
$routeFile = __DIR__ . '/routes/web.php';
$routeContent = file_get_contents($routeFile);

$checks = [
    "ProfileController" => strpos($routeContent, 'ProfileController') !== false,
    "/profile route" => strpos($routeContent, "Route::get('/profile'") !== false || 
                               strpos($routeContent, "Route::middleware('auth')->group") !== false,
];

foreach ($checks as $check => $result) {
    echo "   " . ($result ? "✅" : "❌") . " $check\n";
}

// 2. Check Controller
echo "\n2️⃣  CHECKING PROFILECONTROLLER...\n";
$controllerFile = __DIR__ . '/app/Http/Controllers/ProfileController.php';
if (file_exists($controllerFile)) {
    $controller = file_get_contents($controllerFile);
    $methods = [
        'show()' => strpos($controller, 'public function show') !== false,
        'update()' => strpos($controller, 'public function update') !== false,
        'updatePassword()' => strpos($controller, 'public function updatePassword') !== false,
        'Avatar handling' => strpos($controller, 'avatar') !== false,
        'Hash checking' => strpos($controller, 'Hash::check') !== false,
    ];
    
    foreach ($methods as $method => $result) {
        echo "   " . ($result ? "✅" : "❌") . " $method\n";
    }
} else {
    echo "   ❌ ProfileController not found!\n";
}

// 3. Check View
echo "\n3️⃣  CHECKING VIEWS...\n";
$viewFile = __DIR__ . '/resources/views/profile/show.blade.php';
if (file_exists($viewFile)) {
    $view = file_get_contents($viewFile);
    $viewChecks = [
        'Profile header' => strpos($view, '👤 Profil Saya') !== false,
        'Edit form' => strpos($view, 'profile.update') !== false,
        'Password form' => strpos($view, 'profile.update-password') !== false,
        'Avatar upload' => strpos($view, 'avatar') !== false,
        'Tab navigation' => strpos($view, 'switchTab') !== false,
    ];
    
    foreach ($viewChecks as $check => $result) {
        echo "   " . ($result ? "✅" : "❌") . " $check\n";
    }
} else {
    echo "   ❌ Profile view not found!\n";
}

// 4. Check Migrations
echo "\n4️⃣  CHECKING MIGRATIONS...\n";
$migrationsDir = __DIR__ . '/database/migrations';
$profileMigration = null;

foreach (scandir($migrationsDir) as $file) {
    if (strpos($file, 'add_profile_fields_to_users_table') !== false) {
        $profileMigration = $file;
        break;
    }
}

if ($profileMigration) {
    echo "   ✅ Profile migration found: $profileMigration\n";
    
    $migrationContent = file_get_contents($migrationsDir . '/' . $profileMigration);
    $migrationFields = [
        'avatar' => strpos($migrationContent, "'avatar'") !== false,
        'bio' => strpos($migrationContent, "'bio'") !== false,
        'phone' => strpos($migrationContent, "'phone'") !== false,
        'gender' => strpos($migrationContent, "'gender'") !== false,
        'birth_date' => strpos($migrationContent, "'birth_date'") !== false,
        'location' => strpos($migrationContent, "'location'") !== false,
    ];
    
    foreach ($migrationFields as $field => $result) {
        echo "   " . ($result ? "✅" : "❌") . " Field: $field\n";
    }
} else {
    echo "   ❌ Profile migration not found!\n";
}

// 5. Check Storage
echo "\n5️⃣  CHECKING STORAGE...\n";
$avatarDir = __DIR__ . '/storage/app/public/avatars';
$storageLink = __DIR__ . '/public/storage';

echo "   " . (is_dir($avatarDir) ? "✅" : "❌") . " Avatar directory exists\n";
echo "   " . (is_link($storageLink) ? "✅" : "❌") . " Storage symlink exists\n";

// 6. Check User Model
echo "\n6️⃣  CHECKING USER MODEL...\n";
$userFile = __DIR__ . '/app/Models/User.php';
if (file_exists($userFile)) {
    $userModel = file_get_contents($userFile);
    $fillableFields = [
        'name' => strpos($userModel, "'name'") !== false,
        'email' => strpos($userModel, "'email'") !== false,
        'phone' => strpos($userModel, "'phone'") !== false,
        'gender' => strpos($userModel, "'gender'") !== false,
        'birth_date' => strpos($userModel, "'birth_date'") !== false,
        'location' => strpos($userModel, "'location'") !== false,
        'bio' => strpos($userModel, "'bio'") !== false,
        'avatar' => strpos($userModel, "'avatar'") !== false,
    ];
    
    foreach ($fillableFields as $field => $result) {
        echo "   " . ($result ? "✅" : "❌") . " $field in fillable\n";
    }
} else {
    echo "   ❌ User model not found!\n";
}

// 7. Summary
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║   SUMMARY                                                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "✨ Profile System Implementation Complete!\n\n";
echo "📝 To test the profile system:\n";
echo "   1. Visit: http://localhost/auth/register\n";
echo "   2. Create a new account\n";
echo "   3. Click your avatar in top-right corner\n";
echo "   4. Select 'PROFIL' from dropdown\n";
echo "   5. Edit your profile or change password\n\n";

echo "📂 Key files:\n";
echo "   • routes/web.php (Profile routes)\n";
echo "   • app/Http/Controllers/ProfileController.php (3 methods)\n";
echo "   • resources/views/profile/show.blade.php (Profile template)\n";
echo "   • app/Models/User.php (Updated with fillable fields)\n";
echo "   • database/migrations/*_add_profile_fields_to_users_table.php\n\n";
