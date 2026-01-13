<?php

/**
 * Make User Admin Script
 * Set user as admin to access Filament panel
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   MAKE USER ADMIN                                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Get user email
echo "Enter email address: ";
$email = trim(fgets(STDIN));

// Find user
$user = User::where('email', $email)->first();

if (!$user) {
    echo "\n❌ User with email '$email' not found!\n\n";
    exit(1);
}

// Check if already admin or superadmin
if ($user->role === User::ROLE_SUPERADMIN) {
    echo "\n✅ User '{$user->name}' is already a SUPERADMIN!\n\n";
    exit(0);
}

if ($user->isAdmin()) {
    echo "\n✅ User '{$user->name}' is already an admin!\n\n";
    exit(0);
}

// Make admin
$user->role = User::ROLE_ADMIN;
$user->is_admin = true;
$user->save();

echo "\n✅ SUCCESS! User '{$user->name}' is now an admin!\n";
echo "   Mereka bisa akses: http://localhost/admin\n\n";
