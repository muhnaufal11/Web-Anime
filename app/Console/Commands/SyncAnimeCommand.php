<?php

namespace App\Console\Commands;

use App\Models\ScrapeConfig;
use App\Models\ScrapeLog;
use App\Services\MyAnimeListService;
use App\Services\AnimeSailService;
use Illuminate\Console\Command;

class SyncAnimeCommand extends Command
{
    protected $signature = 'anime:sync 
                            {--source=both : Source to sync from (myanimelist, animesail, both)}
                            {--type=both : Type of sync (metadata, episodes, both)}
                            {--limit=25 : Maximum number of items to process}
                            {--config= : Use specific scrape config ID}';

    protected $description = 'Sync anime data from MyAnimeList and AnimeSail';

    protected $malService;
    protected $sailService;
    protected $log;

    public function __construct(MyAnimeListService $malService, AnimeSailService $sailService)
    {
        parent::__construct();
        $this->malService = $malService;
        $this->sailService = $sailService;
    }

    public function handle()
    {
        $configId = $this->option('config');
        $source = $this->option('source');
        $type = $this->option('type');
        $limit = (int)$this->option('limit');

        // Create log entry
        $this->log = ScrapeLog::create([
            'scrape_config_id' => $configId,
            'source' => $source === 'both' ? 'myanimelist' : $source,
            'type' => $type === 'both' ? 'full' : $type,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->info("🚀 Starting anime sync...");
        $this->info("Source: {$source} | Type: {$type} | Limit: {$limit}");

        try {
            $stats = [
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'failed' => 0,
            ];

            // Sync from MyAnimeList
            if (in_array($source, ['myanimelist', 'both']) && in_array($type, ['metadata', 'both'])) {
                $this->info("\n📥 Fetching from MyAnimeList...");
                $malStats = $this->syncFromMAL($limit);
                
                $stats['processed'] += $malStats['processed'];
                $stats['created'] += $malStats['created'];
                $stats['updated'] += $malStats['updated'];
                $stats['failed'] += $malStats['failed'];
            }

            // Sync from AnimeSail
            if (in_array($source, ['animesail', 'both']) && in_array($type, ['episodes', 'both'])) {
                $this->info("\n📥 Fetching from AnimeSail...");
                $sailStats = $this->syncFromAnimeSail($limit);
                
                $stats['processed'] += $sailStats['processed'];
                $stats['created'] += $sailStats['created'];
                $stats['updated'] += $sailStats['updated'];
                $stats['failed'] += $sailStats['failed'];
            }

            // Update log
            $this->log->update([
                'status' => $stats['failed'] > 0 ? 'partial' : 'success',
                'items_processed' => $stats['processed'],
                'items_created' => $stats['created'],
                'items_updated' => $stats['updated'],
                'items_failed' => $stats['failed'],
                'completed_at' => now(),
                'message' => "Sync completed successfully",
            ]);

            $this->newLine();
            $this->info("✅ Sync completed!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Processed', $stats['processed']],
                    ['Created', $stats['created']],
                    ['Updated', $stats['updated']],
                    ['Failed', $stats['failed']],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Sync failed: " . $e->getMessage());
            
            $this->log->update([
                'status' => 'failed',
                'completed_at' => now(),
                'message' => $e->getMessage(),
                'errors' => [$e->getTraceAsString()],
            ]);

            return Command::FAILURE;
        }
    }

    protected function syncFromMAL($limit)
    {
        $stats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'failed' => 0];

        // Fetch seasonal anime
        $animeList = $this->malService->fetchSeasonalAnime(null, null, $limit);
        
        $progressBar = $this->output->createProgressBar(count($animeList));
        $progressBar->start();

        foreach ($animeList as $malData) {
            try {
                $anime = $this->malService->syncAnime($malData, true);
                
                if ($anime->wasRecentlyCreated) {
                    $stats['created']++;
                    $this->newLine();
                    $this->info("✨ Created: {$anime->title}");
                } else {
                    $stats['updated']++;
                }
                
                $stats['processed']++;
                
            } catch (\Exception $e) {
                $stats['failed']++;
                $this->newLine();
                $this->error("Failed: " . ($malData['title'] ?? 'Unknown') . " - " . $e->getMessage());
            }
            
            $progressBar->advance();
            
            // Rate limiting
            usleep(350000);
        }

        $progressBar->finish();
        $this->newLine();

        return $stats;
    }

    protected function syncFromAnimeSail($limit)
    {
        $stats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'failed' => 0];

        // Get existing anime to sync episodes
        $animes = \App\Models\Anime::limit($limit)->get();

        if ($animes->isEmpty()) {
            $this->warn("No anime found in database. Sync from MyAnimeList first.");
            return $stats;
        }

        $progressBar = $this->output->createProgressBar($animes->count());
        $progressBar->start();

        foreach ($animes as $anime) {
            try {
                // Search on AnimeSail
                $searchResults = $this->sailService->searchAnime($anime->title);
                
                if (!empty($searchResults)) {
                    $firstResult = $searchResults[0];
                    
                    $this->newLine();
                    $this->info("🔗 Syncing episodes for: {$anime->title}");
                    
                    $result = $this->sailService->syncEpisodesForAnime($anime, $firstResult['url']);
                    
                    $stats['created'] += $result['created'];
                    $stats['updated'] += $result['updated'];
                    
                    if (!empty($result['errors'])) {
                        $stats['failed'] += count($result['errors']);
                    }
                }
                
                $stats['processed']++;
                
            } catch (\Exception $e) {
                $stats['failed']++;
                $this->newLine();
                $this->error("Failed: {$anime->title} - " . $e->getMessage());
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $stats;
    }
}

