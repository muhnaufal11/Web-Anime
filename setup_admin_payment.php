<?php

// Set admin_start_date untuk admin yang belum punya

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

$updated = User::whereIn('role', ['admin_upload', 'admin_sync'])
    ->whereNull('admin_start_date')
    ->update(['admin_start_date' => now()]);

echo "Updated {$updated} admin(s) with admin_start_date\n";

// Show current admins with their payment info
echo "\n=== Current Admins ===\n";
$admins = User::whereIn('role', ['admin_upload', 'admin_sync', 'superadmin'])->get();

foreach ($admins as $admin) {
    echo "\n{$admin->name} ({$admin->role})\n";
    echo "  - Level: " . $admin->getAdminLevelLabel() . "\n";
    echo "  - Rate: Rp " . number_format($admin->payment_rate ?? 500, 0, ',', '.') . "\n";
    echo "  - Monthly Limit: " . ($admin->getMonthlyLimit() ? 'Rp ' . number_format($admin->getMonthlyLimit(), 0, ',', '.') : 'Unlimited') . "\n";
    echo "  - Rollover: Rp " . number_format($admin->rollover_balance ?? 0, 0, ',', '.') . "\n";
    echo "  - Start Date: " . ($admin->admin_start_date ? $admin->admin_start_date->format('d M Y') : 'N/A') . "\n";
    
    $calc = $admin->calculateMonthlyPayment(now()->year, now()->month);
    echo "  - This Month Payment:\n";
    echo "    * Pending: Rp " . number_format($calc['pending_this_month'], 0, ',', '.') . "\n";
    echo "    * Rollover: Rp " . number_format($calc['rollover_from_previous'], 0, ',', '.') . "\n";
    echo "    * Payable: Rp " . number_format($calc['payable'], 0, ',', '.') . "\n";
    echo "    * Next Rollover: Rp " . number_format($calc['rollover_to_next'], 0, ',', '.') . "\n";
}
