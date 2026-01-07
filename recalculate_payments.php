<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AdminEpisodeLog;
use App\Models\User;

echo "=== Admin Users & Rates ===\n\n";

$admins = User::where('role', '!=', 'user')->get();
foreach ($admins as $u) {
    echo "ID: {$u->id} | {$u->name} | Role: {$u->role} | Rate: Rp " . number_format($u->payment_rate ?? 500, 0, ',', '.') . "\n";
}

echo "\n=== Recalculating Pending Logs ===\n\n";

// Get all users with pending logs
$users = User::whereHas('adminEpisodeLogs', function($q) {
    $q->where('status', 'pending');
})->get();

foreach ($users as $user) {
    $rate = $user->payment_rate ?? 500;
    $pendingLogs = AdminEpisodeLog::where('user_id', $user->id)
        ->where('status', 'pending')
        ->get();
    
    $oldTotal = $pendingLogs->sum('amount');
    $count = $pendingLogs->count();
    
    // Update all pending logs with user's current rate
    AdminEpisodeLog::where('user_id', $user->id)
        ->where('status', 'pending')
        ->update(['amount' => $rate]);
    
    $newTotal = $rate * $count;
    
    echo "User: {$user->name}\n";
    echo "  Rate: Rp " . number_format($rate, 0, ',', '.') . "\n";
    echo "  Pending logs: {$count}\n";
    echo "  Old total: Rp " . number_format($oldTotal, 0, ',', '.') . "\n";
    echo "  New total: Rp " . number_format($newTotal, 0, ',', '.') . "\n";
    echo "---\n";
}

echo "\n✅ Done!\n";
