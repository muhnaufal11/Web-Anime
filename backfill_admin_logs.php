#!/usr/bin/env php
<?php

/**
 * Backfill Admin Episode Logs
 * Create logs untuk episode yang sudah ada tapi belum tercatat
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Episode;
use App\Models\User;
use App\Models\AdminEpisodeLog;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   BACKFILL ADMIN EPISODE LOGS                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// List all admins
$admins = User::admins()->get();

if ($admins->isEmpty()) {
    echo "❌ Tidak ada admin!\n\n";
    exit(1);
}

echo "👥 Admin yang tersedia:\n";
foreach ($admins as $i => $admin) {
    echo "   " . ($i + 1) . ". {$admin->name} ({$admin->email})\n";
}

echo "\nPilih nomor admin (atau tekan Enter untuk semua): ";
$choice = trim(fgets(STDIN));

if (empty($choice)) {
    // All admins
    $selectedAdmins = $admins;
} else {
    $index = intval($choice) - 1;
    if (!isset($admins[$index])) {
        echo "❌ Pilihan tidak valid!\n\n";
        exit(1);
    }
    $selectedAdmins = collect([$admins[$index]]);
}

echo "\n";

$totalCreated = 0;
$totalSkipped = 0;

foreach ($selectedAdmins as $admin) {
    // Get episodes that don't have logs yet
    $episodesWithoutLogs = Episode::where('created_by', $admin->id)
        ->orWhere(function ($query) use ($admin) {
            $query->whereDoesntHave('adminEpisodeLogs')
                ->limit(100); // Get random 100 episodes without logs
        })
        ->get();

    if ($episodesWithoutLogs->isEmpty()) {
        echo "✅ {$admin->name}: Tidak ada episode baru untuk di-track\n";
        continue;
    }

    $created = 0;
    foreach ($episodesWithoutLogs as $episode) {
        $exists = AdminEpisodeLog::where('user_id', $admin->id)
            ->where('episode_id', $episode->id)
            ->exists();

        if (!$exists) {
            AdminEpisodeLog::create([
                'user_id' => $admin->id,
                'episode_id' => $episode->id,
                'amount' => AdminEpisodeLog::DEFAULT_AMOUNT,
                'status' => AdminEpisodeLog::STATUS_PENDING,
            ]);
            $created++;
            $totalCreated++;
        } else {
            $totalSkipped++;
        }
    }

    echo "✅ {$admin->name}: Created {$created} logs\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 SUMMARY:\n";
echo "   Total Created: {$totalCreated}\n";
echo "   Total Skipped: {$totalSkipped}\n";
echo "\n✨ Backfill complete!\n\n";
