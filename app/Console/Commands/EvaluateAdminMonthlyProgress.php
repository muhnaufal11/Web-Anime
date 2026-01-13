<?php

namespace App\Console\Commands;

use App\Services\AdminProgressService;
use Illuminate\Console\Command;

class EvaluateAdminMonthlyProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:evaluate-monthly 
                            {--year= : Year to evaluate (default: previous month year)}
                            {--month= : Month to evaluate (default: previous month)}
                            {--notify : Send results to Discord}
                            {--dry-run : Preview results without updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate monthly admin performance with Hard Mode leveling system';

    /**
     * Execute the console command.
     */
    public function handle(AdminProgressService $progressService): int
    {
        $year = $this->option('year') ?? now()->subMonth()->year;
        $month = $this->option('month') ?? now()->subMonth()->month;
        $notify = $this->option('notify');
        $dryRun = $this->option('dry-run');

        $this->info("🔄 Evaluating admin progress for " . \Carbon\Carbon::create($year, $month, 1)->format('F Y'));
        $this->newLine();

        if ($dryRun) {
            $this->warn("⚠️  DRY RUN MODE - No changes will be saved to database");
            $this->newLine();
        }

        // Get evaluation results
        $results = $progressService->evaluateAllAdmins($year, $month);

        if (empty($results)) {
            $this->warn('No admins found to evaluate.');
            return 0;
        }

        // Display results
        $this->displayResults($results);

        // Send to Discord if requested
        if ($notify && !$dryRun) {
            $this->info("📤 Sending results to Discord...");
            $progressService->sendEvaluationToDiscord($results);
            $this->info("✅ Results sent to Discord!");
        }

        $this->newLine();
        $this->info("✅ Evaluation complete!");

        return 0;
    }

    /**
     * Display evaluation results in table format
     */
    protected function displayResults(array $results): void
    {
        $headers = ['Admin', 'Level', 'Streak', 'Work', 'Days', 'Approval', 'Action', 'Message'];
        $rows = [];

        foreach ($results as $result) {
            $levelChange = $result['current_level'] === $result['new_level'] 
                ? $result['current_level'] 
                : "{$result['current_level']} → {$result['new_level']}";

            $streakChange = $result['previous_consecutive_months'] === $result['new_consecutive_months']
                ? $result['new_consecutive_months']
                : "{$result['previous_consecutive_months']} → {$result['new_consecutive_months']}";

            $actionEmoji = match($result['action']) {
                'promote' => '⬆️',
                'demote' => '⬇️',
                'reset' => '🔄',
                'progress' => '✅',
                default => '➖',
            };

            $rows[] = [
                $result['admin_name'],
                $levelChange,
                $streakChange,
                "{$result['stats']['total_work']}/{$result['target']['target']}",
                "{$result['stats']['days_active']}/26",
                round($result['stats']['approval_rate']) . '%',
                $actionEmoji . ' ' . ucfirst($result['action']),
                \Illuminate\Support\Str::limit($result['message'], 40),
            ];
        }

        $this->table($headers, $rows);

        // Summary
        $this->newLine();
        $promotions = count(array_filter($results, fn($r) => $r['action'] === 'promote'));
        $demotions = count(array_filter($results, fn($r) => $r['action'] === 'demote'));
        $resets = count(array_filter($results, fn($r) => $r['action'] === 'reset'));
        $progress = count(array_filter($results, fn($r) => $r['action'] === 'progress'));

        $this->info("📊 Summary:");
        $this->line("  ⬆️  Promotions: {$promotions}");
        $this->line("  ⬇️  Demotions: {$demotions}");
        $this->line("  🔄 Resets: {$resets}");
        $this->line("  ✅ Progress: {$progress}");
    }
}
