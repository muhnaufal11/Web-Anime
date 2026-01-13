<?php

// Recalculate payment logs berdasarkan user payment_rate

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\AdminEpisodeLog;

echo "=== Recalculating Payment Logs ===\n\n";

// Get all admins
$admins = User::whereIn('role', ['admin_upload', 'admin_sync'])->get();

$totalFixed = 0;
$totalDifference = 0;

foreach ($admins as $admin) {
    $rate = $admin->payment_rate ?? 500;
    
    // Get pending logs with wrong amount
    $wrongLogs = AdminEpisodeLog::where('user_id', $admin->id)
        ->where('status', AdminEpisodeLog::STATUS_PENDING)
        ->where('amount', '!=', $rate)
        ->get();
    
    if ($wrongLogs->count() > 0) {
        echo "Admin: {$admin->name} (Rate: Rp " . number_format($rate, 0, ',', '.') . ")\n";
        
        foreach ($wrongLogs as $log) {
            $oldAmount = $log->amount;
            $difference = $rate - $oldAmount;
            
            echo "  - Log #{$log->id}: Rp " . number_format($oldAmount, 0, ',', '.') . " -> Rp " . number_format($rate, 0, ',', '.') . " (+Rp " . number_format($difference, 0, ',', '.') . ")\n";
            
            // Update the log
            $log->update([
                'amount' => $rate,
                'note' => $log->note . ' [Fixed: ' . $oldAmount . ' -> ' . $rate . ']'
            ]);
            
            $totalFixed++;
            $totalDifference += $difference;
        }
        echo "\n";
    }
}

echo "=== Summary ===\n";
echo "Total logs fixed: {$totalFixed}\n";
echo "Total difference added: Rp " . number_format($totalDifference, 0, ',', '.') . "\n";

// Show updated payment info
echo "\n=== Updated Payment Info ===\n";
foreach ($admins as $admin) {
    $admin->refresh();
    $calc = $admin->calculateMonthlyPayment(now()->year, now()->month);
    
    echo "\n{$admin->name}:\n";
    echo "  Pending: Rp " . number_format($calc['pending_this_month'], 0, ',', '.') . "\n";
    echo "  Payable: Rp " . number_format($calc['payable'], 0, ',', '.') . "\n";
    if ($calc['rollover_to_next'] > 0) {
        echo "  Rollover: Rp " . number_format($calc['rollover_to_next'], 0, ',', '.') . "\n";
    }
}
