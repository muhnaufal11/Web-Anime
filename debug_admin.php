<?php

/**
 * Debug Admin Access
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   DEBUG ADMIN ACCESS                                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Check user directly from database
$user = DB::table('users')->where('email', 'naufalrabbani146@gmail.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "📊 USER DATA FROM DATABASE:\n";
echo "   ID: {$user->id}\n";
echo "   Name: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   role: " . ($user->role ?? '-') . "\n";
echo "   is_admin flag: " . ($user->is_admin ? "✅ TRUE (1)" : "❌ FALSE (0)") . "\n";
echo "   Raw value: " . var_export($user->is_admin, true) . "\n\n";

// Check via Eloquent
$eloquentUser = User::find($user->id);
echo "📊 USER DATA FROM ELOQUENT:\n";
echo "   ID: {$eloquentUser->id}\n";
echo "   Name: {$eloquentUser->name}\n";
echo "   Email: {$eloquentUser->email}\n";
echo "   role: {$eloquentUser->role}\n";
echo "   is_admin accessor: " . var_export($eloquentUser->is_admin, true) . "\n";
echo "   isSuperAdmin(): " . ($eloquentUser->isSuperAdmin() ? 'YES' : 'NO') . "\n";

// Check canAccessFilament
try {
    $canAccess = $eloquentUser->canAccessFilament();
    echo "   canAccessFilament(): " . ($canAccess ? "✅ TRUE" : "❌ FALSE") . "\n";
} catch (Exception $e) {
    echo "   canAccessFilament(): ❌ ERROR - {$e->getMessage()}\n";
}


echo "\n";
