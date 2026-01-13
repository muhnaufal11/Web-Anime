<?php

namespace App\Services;

use App\Models\User;
use App\Models\AdminEpisodeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminProgressService
{
    protected string $webhookUrl = 'https://discordapp.com/api/webhooks/1458057481858318426/d_VyQ5Uo1tygqLGVW2s-8uAg483oT42D1f2vNb-8ofA2eOKP4q68HH-iibRB7ICmW0K-';

    /**
     * Target baru per level (Hard Mode - Linear Progression)
     * 
     * Uploader: Rate ~Rp 10k/item
     * Syncer: Rate ~Rp 1.5k/item
     */
    public const LEVEL_TARGETS = [
        User::ROLE_ADMIN_UPLOAD => [
            1 => ['target' => 50, 'cap' => 500000],      // Level 1: 50 upload, Cap 500k
            2 => ['target' => 80, 'cap' => 800000],      // Level 2: 80 upload, Cap 800k
            3 => ['target' => 110, 'cap' => 1100000],    // Level 3: 110 upload, Cap 1.1jt
            4 => ['target' => 140, 'cap' => 1400000],    // Level 4: 140 upload, Cap 1.4jt
            5 => ['target' => 180, 'cap' => 1800000],    // Level 5: 180 upload, Cap 1.8jt
        ],
        User::ROLE_ADMIN_SYNC => [
            1 => ['target' => 200, 'cap' => 300000],     // Level 1: 200 sync, Cap 300k
            2 => ['target' => 350, 'cap' => 525000],     // Level 2: 350 sync, Cap 525k
            3 => ['target' => 500, 'cap' => 750000],     // Level 3: 500 sync, Cap 750k
            4 => ['target' => 700, 'cap' => 1050000],    // Level 4: 700 sync, Cap 1.05jt
            5 => ['target' => 900, 'cap' => 1350000],    // Level 5: 900 sync, Cap 1.35jt
        ],
    ];

    /**
     * Jumlah bulan konsekutif yang diperlukan untuk naik level
     */
    public const MONTHS_REQUIRED_FOR_PROMOTION = 6;

    /**
     * Minimum hari aktif per bulan
     */
    public const MIN_ACTIVE_DAYS = 26;

    /**
     * Get admin category label
     */
    public function getAdminCategory(User $admin): string
    {
        return match($admin->role) {
            User::ROLE_ADMIN_SYNC => 'Sync',
            User::ROLE_ADMIN_UPLOAD => 'Upload',
            default => 'Admin',
        };
    }

    /**
     * Get admin category emoji
     */
    public function getAdminCategoryEmoji(User $admin): string
    {
        return match($admin->role) {
            User::ROLE_ADMIN_SYNC => '🔄',
            User::ROLE_ADMIN_UPLOAD => '📤',
            default => '👤',
        };
    }

    /**
     * Get admin progress metrics for current month
     */
    public function getAdminProgress(User $admin): array
    {
        $year = now()->year;
        $month = now()->month;
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // Total episodes this month
        $totalEpisodes = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Episodes by type
        $syncEpisodes = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('amount', AdminEpisodeLog::AMOUNT_SYNC)
            ->count();

        $uploadEpisodes = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('amount', AdminEpisodeLog::AMOUNT_UPLOAD)
            ->count();

        // Total earnings
        $totalEarnings = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Approved vs Pending vs Rejected ratio
        $approvedCount = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', AdminEpisodeLog::STATUS_APPROVED)
            ->count();

        $pendingCount = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', AdminEpisodeLog::STATUS_PENDING)
            ->count();

        $rejectedCount = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', AdminEpisodeLog::STATUS_REJECTED)
            ->count();

        $paidCount = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', AdminEpisodeLog::STATUS_PAID)
            ->count();

        // Active days (unique days with work)
        $activeDays = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as work_date')
            ->groupBy('work_date')
            ->get()
            ->count();

        $daysInMonth = now()->daysInMonth;
        $daysPassed = min(now()->day, $daysInMonth);

        // Average per day
        $avgPerDay = $daysPassed > 0 ? round($totalEpisodes / $daysPassed, 1) : 0;

        // Consistency score (percentage of days active)
        $consistencyScore = $daysPassed > 0 ? round(($activeDays / $daysPassed) * 100) : 0;

        // Approval rate (rejected mempengaruhi approval rate!)
        // Formula: (approved + paid) / (approved + paid + rejected) * 100
        // Pending tidak dihitung karena belum diproses
        $totalProcessed = $approvedCount + $paidCount;
        $totalDecided = $totalProcessed + $rejectedCount;
        $approvalRate = $totalDecided > 0 ? round(($totalProcessed / $totalDecided) * 100) : 100; // 100% jika belum ada yang diproses

        // Admin category
        $category = $this->getAdminCategory($admin);
        $categoryEmoji = $this->getAdminCategoryEmoji($admin);

        // Get level target (Hard Mode)
        $currentLevel = $admin->admin_level ?? 1;
        $levelTarget = $this->getTargetForLevel($admin->role, $currentLevel);

        // Performance score (weighted based on category)
        $performanceScore = $this->calculatePerformanceScore([
            'total_episodes' => $totalEpisodes,
            'sync_episodes' => $syncEpisodes,
            'upload_episodes' => $uploadEpisodes,
            'consistency' => $consistencyScore,
            'approval_rate' => $approvalRate,
            'avg_per_day' => $avgPerDay,
            'category' => $category,
        ]);

        // Level recommendation
        $levelRecommendation = $this->getLevelRecommendation($admin, $performanceScore);

        return [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'admin_level' => $admin->admin_level,
            'level_label' => $admin->getAdminLevelLabel(),
            'category' => $category,
            'category_emoji' => $categoryEmoji,
            'role' => $admin->role,
            'month' => now()->format('F Y'),
            
            // Level target (Hard Mode)
            'level_target' => $levelTarget['target'],
            'level_cap' => $levelTarget['cap'],
            'consecutive_months' => $admin->consecutive_success_months ?? 0,
            
            // Episode counts
            'total_episodes' => $totalEpisodes,
            'sync_episodes' => $syncEpisodes,
            'upload_episodes' => $uploadEpisodes,
            
            // Earnings
            'total_earnings' => $totalEarnings,
            'pending_amount' => $pendingCount * AdminEpisodeLog::DEFAULT_AMOUNT,
            'approved_amount' => $admin->getApprovedEarningsForMonth($year, $month),
            
            // Status breakdown
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'paid_count' => $paidCount,
            
            // Performance metrics
            'active_days' => $activeDays,
            'days_passed' => $daysPassed,
            'avg_per_day' => $avgPerDay,
            'consistency_score' => $consistencyScore,
            'approval_rate' => $approvalRate,
            'performance_score' => $performanceScore,
            
            // Recommendation
            'level_recommendation' => $levelRecommendation,
        ];
    }

    /**
     * Calculate weighted performance score based on admin category
     */
    protected function calculatePerformanceScore(array $metrics): int
    {
        $score = 0;
        $category = $metrics['category'] ?? 'Admin';

        if ($category === 'Sync') {
            // Admin Sync: fokus pada jumlah sync
            // Sync count (max 50 points) - target 200 sync/bulan = 50 points
            $syncScore = min(50, $metrics['sync_episodes'] * 0.25);
            $score += $syncScore;

            // Consistency (max 30 points)
            $consistencyScore = $metrics['consistency'] * 0.3;
            $score += $consistencyScore;

            // Approval rate (max 20 points)
            $approvalScore = $metrics['approval_rate'] * 0.2;
            $score += $approvalScore;

        } elseif ($category === 'Upload') {
            // Admin Upload: fokus pada jumlah upload manual
            // Upload count (max 50 points) - target 50 upload/bulan = 50 points
            $uploadScore = min(50, $metrics['upload_episodes'] * 1);
            $score += $uploadScore;

            // Consistency (max 30 points)
            $consistencyScore = $metrics['consistency'] * 0.3;
            $score += $consistencyScore;

            // Approval rate (max 20 points)
            $approvalScore = $metrics['approval_rate'] * 0.2;
            $score += $approvalScore;

        } else {
            // Legacy admin: combined scoring
            $episodeScore = min(40, $metrics['total_episodes'] * 0.4);
            $score += $episodeScore;

            $uploadScore = min(20, $metrics['upload_episodes'] * 2);
            $score += $uploadScore;

            $consistencyScore = $metrics['consistency'] * 0.2;
            $score += $consistencyScore;

            $approvalScore = $metrics['approval_rate'] * 0.2;
            $score += $approvalScore;
        }

        return (int) min(100, round($score));
    }

    /**
     * Get level recommendation based on performance
     */
    protected function getLevelRecommendation(User $admin, int $performanceScore): array
    {
        $currentLevel = $admin->admin_level ?? 1;
        $consecutiveMonths = $admin->consecutive_success_months ?? 0;
        $target = $this->getTargetForLevel($admin->role, $currentLevel);
        
        $recommendation = [
            'current_level' => $currentLevel,
            'recommended_level' => $currentLevel,
            'action' => 'maintain',
            'reason' => '',
            'consecutive_months' => $consecutiveMonths,
            'months_to_promotion' => self::MONTHS_REQUIRED_FOR_PROMOTION - $consecutiveMonths,
            'target' => $target,
        ];

        // Hard Mode: Butuh 6 bulan berturut-turut untuk naik level
        if ($consecutiveMonths >= self::MONTHS_REQUIRED_FOR_PROMOTION && $currentLevel < 5) {
            $recommendation['recommended_level'] = $currentLevel + 1;
            $recommendation['action'] = 'promote';
            $recommendation['reason'] = "🎉 Selamat! {$consecutiveMonths} bulan berturut-turut memenuhi target. Siap naik level!";
        } elseif ($performanceScore < 30 && $currentLevel > 1) {
            $recommendation['recommended_level'] = $currentLevel - 1;
            $recommendation['action'] = 'demote';
            $recommendation['reason'] = "⚠️ Performa sangat rendah ({$performanceScore}/100) - akan turun level";
        } else {
            $monthsLeft = self::MONTHS_REQUIRED_FOR_PROMOTION - $consecutiveMonths;
            $recommendation['reason'] = "📊 {$consecutiveMonths}/{self::MONTHS_REQUIRED_FOR_PROMOTION} bulan berturut-turut. {$monthsLeft} bulan lagi untuk naik level.";
        }

        return $recommendation;
    }

    /**
     * Get target for specific role and level
     */
    public function getTargetForLevel(string $role, int $level): array
    {
        return self::LEVEL_TARGETS[$role][$level] ?? self::LEVEL_TARGETS[$role][1] ?? [
            'target' => 50,
            'cap' => 500000,
        ];
    }

    /**
     * Get admin's monthly work count based on their category
     */
    public function getMonthlyWorkCount(User $admin, ?int $year = null, ?int $month = null): int
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        if ($admin->role === User::ROLE_ADMIN_SYNC) {
            return $admin->adminEpisodeLogs()
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('amount', AdminEpisodeLog::AMOUNT_SYNC)
                ->count();
        } elseif ($admin->role === User::ROLE_ADMIN_UPLOAD) {
            return $admin->adminEpisodeLogs()
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('amount', AdminEpisodeLog::AMOUNT_UPLOAD)
                ->count();
        }

        // Legacy: count all
        return $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
    }

    /**
     * Get monthly stats for evaluation
     */
    public function getMonthlyStats(User $admin, ?int $year = null, ?int $month = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $totalWork = $this->getMonthlyWorkCount($admin, $year, $month);

        // Approved & Paid count
        $approvedCount = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', AdminEpisodeLog::STATUS_APPROVED)
            ->count();

        $paidCount = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', AdminEpisodeLog::STATUS_PAID)
            ->count();

        $pendingCount = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', AdminEpisodeLog::STATUS_PENDING)
            ->count();

        $rejectedCount = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', AdminEpisodeLog::STATUS_REJECTED)
            ->count();

        // Active days (fixed query)
        $activeDays = $admin->adminEpisodeLogs()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as work_date')
            ->groupBy('work_date')
            ->get()
            ->count();

        // Approval rate (rejected mempengaruhi approval rate!)
        // Formula: (approved + paid) / (approved + paid + rejected) * 100
        // Pending tidak dihitung karena belum diproses
        $totalProcessed = $approvedCount + $paidCount;
        $totalDecided = $totalProcessed + $rejectedCount;
        $approvalRate = $totalDecided > 0 ? ($totalProcessed / $totalDecided) * 100 : 100;

        return [
            'total_work' => $totalWork,
            'approved_count' => $approvedCount,
            'paid_count' => $paidCount,
            'pending_count' => $pendingCount,
            'rejected_count' => $rejectedCount,
            'days_active' => $activeDays,
            'approval_rate' => $approvalRate,
        ];
    }

    /**
     * Evaluasi Bulanan - "Death Match" Rules
     * 
     * Rules:
     * A. Syarat Wajib (Pass/Fail):
     *    - approval_rate HARUS 100%
     *    - days_active HARUS >= 26 hari
     *    - total_work HARUS >= Target Level saat ini
     * 
     * B. Naik Level (Promotion):
     *    - Jika semua syarat terpenuhi: +1 consecutive_success_months
     *    - Jika consecutive_success_months >= 6: Level Up, reset counter
     * 
     * C. Gagal (Punishment):
     *    - Jika gagal salah satu syarat: reset consecutive_success_months = 0
     * 
     * D. Turun Level (Demotion):
     *    - Jika approval_rate < 90% ATAU total_work < 50% target, DAN Level > 1
     *    - Level Down, reset consecutive_success_months = 0
     * 
     * @param User $admin
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public function evaluateMonthlyProgress(User $admin, ?int $year = null, ?int $month = null): array
    {
        $year = $year ?? now()->subMonth()->year;
        $month = $month ?? now()->subMonth()->month;
        
        $stats = $this->getMonthlyStats($admin, $year, $month);
        $currentLevel = $admin->admin_level ?? 1;
        $target = $this->getTargetForLevel($admin->role, $currentLevel);
        $targetWork = $target['target'];
        
        $result = [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'evaluation_period' => Carbon::create($year, $month, 1)->format('F Y'),
            'current_level' => $currentLevel,
            'new_level' => $currentLevel,
            'previous_consecutive_months' => $admin->consecutive_success_months ?? 0,
            'new_consecutive_months' => $admin->consecutive_success_months ?? 0,
            'stats' => $stats,
            'target' => $target,
            'checks' => [],
            'passed' => false,
            'action' => 'none',
            'message' => '',
        ];

        // === A. Cek Syarat Wajib ===
        $checks = [
            'approval_rate' => [
                'required' => 100,
                'actual' => $stats['approval_rate'],
                'passed' => $stats['approval_rate'] >= 100,
                'label' => 'Approval Rate 100%',
            ],
            'days_active' => [
                'required' => self::MIN_ACTIVE_DAYS,
                'actual' => $stats['days_active'],
                'passed' => $stats['days_active'] >= self::MIN_ACTIVE_DAYS,
                'label' => 'Minimal ' . self::MIN_ACTIVE_DAYS . ' hari aktif',
            ],
            'total_work' => [
                'required' => $targetWork,
                'actual' => $stats['total_work'],
                'passed' => $stats['total_work'] >= $targetWork,
                'label' => 'Target ' . $targetWork . ' pekerjaan',
            ],
        ];

        $result['checks'] = $checks;
        $allPassed = $checks['approval_rate']['passed'] 
                  && $checks['days_active']['passed'] 
                  && $checks['total_work']['passed'];

        // === D. Cek Demotion (sangat buruk) ===
        $halfTarget = $targetWork * 0.5;
        $severelyUnderperformed = $stats['approval_rate'] < 90 || $stats['total_work'] < $halfTarget;

        if ($severelyUnderperformed && $currentLevel > 1) {
            // DEMOTION
            $result['new_level'] = $currentLevel - 1;
            $result['new_consecutive_months'] = 0;
            $result['action'] = 'demote';
            $result['passed'] = false;
            $result['message'] = "⬇️ TURUN LEVEL! Performa sangat buruk (Approval: {$stats['approval_rate']}%, Work: {$stats['total_work']}/{$targetWork})";
            
            // Update admin
            $admin->update([
                'admin_level' => $result['new_level'],
                'consecutive_success_months' => 0,
                'last_evaluation_date' => now(),
            ]);
            
            return $result;
        }

        // === C. Gagal - Reset Counter ===
        if (!$allPassed) {
            $result['new_consecutive_months'] = 0;
            $result['action'] = 'reset';
            $result['passed'] = false;
            
            $failedChecks = [];
            foreach ($checks as $key => $check) {
                if (!$check['passed']) {
                    $failedChecks[] = $check['label'];
                }
            }
            
            $result['message'] = "❌ Tidak memenuhi syarat: " . implode(', ', $failedChecks) . ". Streak direset ke 0.";
            
            // Update admin
            $admin->update([
                'consecutive_success_months' => 0,
                'last_evaluation_date' => now(),
            ]);
            
            return $result;
        }

        // === B. Pass - Increment Counter ===
        $newConsecutive = ($admin->consecutive_success_months ?? 0) + 1;
        $result['passed'] = true;
        $result['new_consecutive_months'] = $newConsecutive;

        // Cek apakah sudah cukup untuk naik level
        if ($newConsecutive >= self::MONTHS_REQUIRED_FOR_PROMOTION && $currentLevel < 5) {
            // PROMOTION!
            $result['new_level'] = $currentLevel + 1;
            $result['new_consecutive_months'] = 0; // Reset setelah naik level
            $result['action'] = 'promote';
            $result['message'] = "🎉 NAIK LEVEL! Selamat atas {$newConsecutive} bulan berturut-turut memenuhi target!";
            
            // Update admin
            $admin->update([
                'admin_level' => $result['new_level'],
                'consecutive_success_months' => 0,
                'last_evaluation_date' => now(),
            ]);
        } else {
            // Masih dalam progress
            $monthsLeft = self::MONTHS_REQUIRED_FOR_PROMOTION - $newConsecutive;
            $result['action'] = 'progress';
            $result['message'] = "✅ Bagus! {$newConsecutive}/" . self::MONTHS_REQUIRED_FOR_PROMOTION . " bulan berturut-turut. {$monthsLeft} bulan lagi untuk naik level.";
            
            // Update admin
            $admin->update([
                'consecutive_success_months' => $newConsecutive,
                'last_evaluation_date' => now(),
            ]);
        }

        return $result;
    }

    /**
     * Evaluate all admins (for monthly cron job)
     */
    public function evaluateAllAdmins(?int $year = null, ?int $month = null): array
    {
        $admins = User::admins()
            ->where('role', '!=', User::ROLE_SUPERADMIN)
            ->get();

        $results = [];
        foreach ($admins as $admin) {
            $results[] = $this->evaluateMonthlyProgress($admin, $year, $month);
        }

        return $results;
    }

    /**
     * Send evaluation results to Discord
     */
    public function sendEvaluationToDiscord(array $evaluationResults): void
    {
        $embeds = [];

        foreach ($evaluationResults as $result) {
            $color = match($result['action']) {
                'promote' => 0x00FF00,  // Green
                'demote' => 0xFF0000,   // Red
                'reset' => 0xFF6600,    // Orange
                'progress' => 0x3498DB, // Blue
                default => 0x808080,    // Gray
            };

            $statusEmoji = match($result['action']) {
                'promote' => '⬆️',
                'demote' => '⬇️',
                'reset' => '🔄',
                'progress' => '✅',
                default => '➖',
            };

            $embeds[] = [
                'title' => "{$statusEmoji} {$result['admin_name']} - Evaluasi {$result['evaluation_period']}",
                'color' => $color,
                'fields' => [
                    [
                        'name' => '📊 Level',
                        'value' => "Level {$result['current_level']} → Level {$result['new_level']}",
                        'inline' => true,
                    ],
                    [
                        'name' => '🔥 Streak',
                        'value' => "{$result['previous_consecutive_months']} → {$result['new_consecutive_months']} bulan",
                        'inline' => true,
                    ],
                    [
                        'name' => '📈 Stats',
                        'value' => "Work: {$result['stats']['total_work']}/{$result['target']['target']} | Days: {$result['stats']['days_active']}/" . self::MIN_ACTIVE_DAYS . " | Approval: {$result['stats']['approval_rate']}%",
                        'inline' => false,
                    ],
                    [
                        'name' => '💬 Hasil',
                        'value' => $result['message'],
                        'inline' => false,
                    ],
                ],
            ];
        }

        if (empty($embeds)) {
            return;
        }

        // Add footer to last embed
        $embeds[count($embeds) - 1]['footer'] = [
            'text' => 'Nipnime Admin Monthly Evaluation - Hard Mode',
        ];
        $embeds[count($embeds) - 1]['timestamp'] = now()->toIso8601String();

        try {
            // Discord allows max 10 embeds per message
            foreach (array_chunk($embeds, 10) as $embedChunk) {
                Http::post($this->webhookUrl, [
                    'content' => '📊 **Monthly Admin Evaluation Results**',
                    'embeds' => $embedChunk,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Discord evaluation webhook failed: ' . $e->getMessage());
        }
    }

    /**
     * Send admin activity update to Discord
     */
    public function sendActivityUpdate(AdminEpisodeLog $log): void
    {
        $admin = $log->user;
        $episode = $log->episode;
        
        if (!$admin || !$episode) {
            return;
        }

        $anime = $episode->anime;
        $progress = $this->getAdminProgress($admin);
        
        $activityType = $log->amount >= AdminEpisodeLog::AMOUNT_UPLOAD ? '📤 Upload' : '🔄 Sync';
        $categoryEmoji = $progress['category_emoji'];
        
        // Progress bar
        $progressBar = $this->generateProgressBar($progress['performance_score']);
        
        // Level emoji
        $levelEmoji = $this->getLevelEmoji($admin->admin_level ?? 1);

        // Target based on category and current level
        $currentLevel = $admin->admin_level ?? 1;
        $levelTarget = $this->getTargetForLevel($admin->role, $currentLevel);
        $targetInfo = match($progress['category']) {
            'Sync' => "🎯 Target Lv{$currentLevel}: {$levelTarget['target']} sync | Progress: {$progress['sync_episodes']}/{$levelTarget['target']}",
            'Upload' => "🎯 Target Lv{$currentLevel}: {$levelTarget['target']} upload | Progress: {$progress['upload_episodes']}/{$levelTarget['target']}",
            default => "🎯 {$progress['total_episodes']} episode bulan ini",
        };

        $embed = [
            'title' => "{$activityType} - {$admin->name}",
            'color' => $log->amount >= AdminEpisodeLog::AMOUNT_UPLOAD ? 0x00FF00 : 0x3498DB,
            'fields' => [
                [
                    'name' => '🎬 Episode',
                    'value' => ($anime->title ?? 'Unknown') . ' - Episode ' . $episode->episode_number,
                    'inline' => false,
                ],
                [
                    'name' => '💰 Reward',
                    'value' => 'Rp ' . number_format($log->amount, 0, ',', '.'),
                    'inline' => true,
                ],
                [
                    'name' => "{$categoryEmoji} Kategori",
                    'value' => "Admin {$progress['category']}",
                    'inline' => true,
                ],
                [
                    'name' => "{$levelEmoji} Level",
                    'value' => $progress['level_label'],
                    'inline' => true,
                ],
                [
                    'name' => '📊 Stats Bulan Ini',
                    'value' => "🔄 Sync: {$progress['sync_episodes']} | 📤 Upload: {$progress['upload_episodes']} | 💰 Rp " . number_format($progress['total_earnings'], 0, ',', '.'),
                    'inline' => false,
                ],
                [
                    'name' => '📈 Performance',
                    'value' => $progressBar . ' ' . $progress['performance_score'] . '/100',
                    'inline' => false,
                ],
                [
                    'name' => '🎯 Progress',
                    'value' => $targetInfo,
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'Nipnime Admin Progress • ' . now()->format('d M Y H:i'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        // Add recommendation if notable
        if ($progress['level_recommendation']['action'] !== 'maintain') {
            $actionEmoji = $progress['level_recommendation']['action'] === 'promote' ? '⬆️' : '⬇️';
            $embed['fields'][] = [
                'name' => "{$actionEmoji} Rekomendasi",
                'value' => $progress['level_recommendation']['reason'],
                'inline' => false,
            ];
        }

        try {
            Http::post($this->webhookUrl, [
                'embeds' => [$embed],
            ]);
        } catch (\Exception $e) {
            Log::error('Discord admin progress webhook failed: ' . $e->getMessage());
        }
    }

    /**
     * Send daily/weekly summary to Discord
     */
    public function sendProgressSummary(): void
    {
        // Get admins by category
        $syncAdmins = User::where('role', User::ROLE_ADMIN_SYNC)->get();
        $uploadAdmins = User::where('role', User::ROLE_ADMIN_UPLOAD)->get();

        $embeds = [];

        // Sync Admins Summary
        if ($syncAdmins->count() > 0) {
            $syncFields = [];
            $totalSync = 0;
            $totalSyncEarnings = 0;

            foreach ($syncAdmins as $admin) {
                $progress = $this->getAdminProgress($admin);
                $totalSync += $progress['sync_episodes'];
                $totalSyncEarnings += $progress['total_earnings'];

                $levelEmoji = $this->getLevelEmoji($admin->admin_level ?? 1);
                $actionEmoji = match($progress['level_recommendation']['action']) {
                    'promote' => ' ⬆️',
                    'demote' => ' ⬇️',
                    default => '',
                };

                $syncFields[] = [
                    'name' => "{$levelEmoji} {$admin->name}{$actionEmoji}",
                    'value' => "📊 {$progress['performance_score']}/100 | 🔄 {$progress['sync_episodes']} sync | 💰 Rp " . number_format($progress['total_earnings'], 0, ',', '.'),
                    'inline' => false,
                ];
            }

            $embeds[] = [
                'title' => '🔄 Admin Sync - ' . now()->format('F Y'),
                'color' => 0x3498DB,
                'description' => "**Total Sync:** {$totalSync} episode\n**Total Earnings:** Rp " . number_format($totalSyncEarnings, 0, ',', '.'),
                'fields' => $syncFields,
            ];
        }

        // Upload Admins Summary
        if ($uploadAdmins->count() > 0) {
            $uploadFields = [];
            $totalUpload = 0;
            $totalUploadEarnings = 0;

            foreach ($uploadAdmins as $admin) {
                $progress = $this->getAdminProgress($admin);
                $totalUpload += $progress['upload_episodes'];
                $totalUploadEarnings += $progress['total_earnings'];

                $levelEmoji = $this->getLevelEmoji($admin->admin_level ?? 1);
                $actionEmoji = match($progress['level_recommendation']['action']) {
                    'promote' => ' ⬆️',
                    'demote' => ' ⬇️',
                    default => '',
                };

                $uploadFields[] = [
                    'name' => "{$levelEmoji} {$admin->name}{$actionEmoji}",
                    'value' => "📊 {$progress['performance_score']}/100 | 📤 {$progress['upload_episodes']} upload | 💰 Rp " . number_format($progress['total_earnings'], 0, ',', '.'),
                    'inline' => false,
                ];
            }

            $embeds[] = [
                'title' => '📤 Admin Upload - ' . now()->format('F Y'),
                'color' => 0x00FF00,
                'description' => "**Total Upload:** {$totalUpload} episode\n**Total Earnings:** Rp " . number_format($totalUploadEarnings, 0, ',', '.'),
                'fields' => $uploadFields,
            ];
        }

        // Add footer to last embed
        if (!empty($embeds)) {
            $embeds[count($embeds) - 1]['footer'] = [
                'text' => 'Nipnime Admin Progress Summary',
            ];
            $embeds[count($embeds) - 1]['timestamp'] = now()->toIso8601String();
        }

        try {
            Http::post($this->webhookUrl, [
                'embeds' => $embeds,
            ]);
        } catch (\Exception $e) {
            Log::error('Discord progress summary webhook failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate text progress bar
     */
    protected function generateProgressBar(int $score): string
    {
        $filled = (int) round($score / 10);
        $empty = 10 - $filled;
        
        return str_repeat('🟩', $filled) . str_repeat('⬜', $empty);
    }

    /**
     * Get emoji for admin level
     */
    protected function getLevelEmoji(int $level): string
    {
        return match($level) {
            1 => '🥉',
            2 => '🥈',
            3 => '🥇',
            4 => '💎',
            5 => '👑',
            default => '🔰',
        };
    }
}
