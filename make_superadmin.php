#!/usr/bin/env php
<?php

/**
 * Make User Superadmin Script
 * Only admins can create superadmins
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   MAKE USER SUPERADMIN                                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Get email from command line
$email = $argv[1] ?? null;

if (!$email) {
    echo "Usage: php make_superadmin.php <email>\n\n";
    exit(1);
}

// Find user
$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ User dengan email '$email' tidak ditemukan!\n\n";
    exit(1);
}

// Check if already superadmin
if ($user->isSuperAdmin()) {
    echo "\n✅ User '{$user->name}' sudah SUPERADMIN!\n\n";
    exit(0);
}

// Make superadmin
$user->role = User::ROLE_SUPERADMIN;
$user->is_admin = true;
$user->save();

echo "\n✅ SUCCESS! User '{$user->name}' is now SUPERADMIN!\n";
echo "   Role: {$user->role}\n";
echo "   Email: {$user->email}\n";
echo "   Akses admin panel: http://localhost/admin\n";
echo "   Full superadmin controls enabled ✨\n\n";
