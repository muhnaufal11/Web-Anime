<?php

/**
 * Quick Make Admin Script
 * Set first user or specific email as admin
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   QUICK MAKE ADMIN                                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Get email from command line or use first user
$email = $argv[1] ?? null;

if ($email) {
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "❌ User with email '$email' not found!\n\n";
        exit(1);
    }
} else {
    // Use first user
    $user = User::first();
    if (!$user) {
        echo "❌ No users found in database!\n\n";
        exit(1);
    }
}

// Make admin
$user->role = User::ROLE_ADMIN;
$user->is_admin = true;
$user->save();

echo "✅ SUCCESS!\n";
echo "   User: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   Status: ADMIN\n\n";
echo "🔑 Can now access: http://localhost/admin\n\n";
