<?php

namespace App\Console\Commands;

use App\Services\MyAnimeListService;
use Illuminate\Console\Command;

class SyncAnimeFromMAL extends Command
{
    protected $signature = 'anime:sync-mal 
                            {--type=seasonal : Type of sync (seasonal, top, search)}
                            {--season= : Season (winter, spring, summer, fall)}
                            {--year= : Year for seasonal anime}
                            {--limit=25 : Number of anime to fetch}
                            {--no-images : Skip downloading images}
                            {--search= : Search query for specific anime}';

    protected $description = 'Sync anime data from MyAnimeList (via Jikan API)';

    protected $malService;

    public function __construct(MyAnimeListService $malService)
    {
        parent::__construct();
        $this->malService = $malService;
    }

    public function handle()
    {
        $type = $this->option('type');
        $limit = (int) $this->option('limit');
        $downloadImages = !$this->option('no-images');

        $this->info("Starting MyAnimeList sync ({$type})...\n");

        try {
            $result = match($type) {
                'seasonal' => $this->syncSeasonal($limit, $downloadImages),
                'top' => $this->syncTop($limit, $downloadImages),
                'search' => $this->syncSearch($limit, $downloadImages),
                default => $this->error("Invalid sync type. Use: seasonal, top, or search")
            };

            if ($result) {
                $this->displayResults($result);
            }
        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function syncSeasonal($limit, $downloadImages)
    {
        $season = $this->option('season');
        $year = $this->option('year');

        $this->info("Syncing seasonal anime ({$season} {$year})...");
        
        $bar = $this->output->createProgressBar($limit);
        $bar->start();

        $result = $this->malService->syncSeasonalAnime($year, $season, $limit, $downloadImages);
        
        $bar->finish();
        $this->newLine(2);

        return $result;
    }

    protected function syncTop($limit, $downloadImages)
    {
        $this->info("Syncing top anime...");
        
        $bar = $this->output->createProgressBar($limit);
        $bar->start();

        $result = $this->malService->syncTopAnime($limit, $downloadImages);
        
        $bar->finish();
        $this->newLine(2);

        return $result;
    }

    protected function syncSearch($limit, $downloadImages)
    {
        $query = $this->option('search');
        
        if (!$query) {
            $this->error("Please provide --search option");
            return null;
        }

        $this->info("Searching for: {$query}...");
        
        $animeList = $this->malService->searchAnime($query, $limit);
        
        if (empty($animeList)) {
            $this->warn("No anime found for query: {$query}");
            return null;
        }

        $bar = $this->output->createProgressBar(count($animeList));
        $bar->start();

        $result = $this->malService->batchSync($animeList, $downloadImages);
        
        $bar->finish();
        $this->newLine(2);

        return $result;
    }

    protected function displayResults($result)
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processed', $result['total']],
                ['Successfully Synced', $result['success_count']],
                ['Failed', $result['failed_count']],
            ]
        );

        if (!empty($result['synced'])) {
            $this->newLine();
            $this->info("✓ Successfully synced anime:");
            foreach ($result['synced'] as $anime) {
                $this->line("  - {$anime['title']} (ID: {$anime['id']})");
            }
        }

        if (!empty($result['failed'])) {
            $this->newLine();
            $this->error("✗ Failed to sync:");
            foreach ($result['failed'] as $failed) {
                $this->line("  - {$failed['title']}: {$failed['error']}");
            }
        }
    }
}
