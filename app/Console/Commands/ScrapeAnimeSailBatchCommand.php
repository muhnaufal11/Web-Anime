<?php

namespace App\Console\Commands;

use App\Services\AnimeSailScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ScrapeAnimeSailBatchCommand extends Command
{
    protected $signature = 'scrape:animesail-batch 
                            {--pages=1 : Number of anime list pages to scrape}
                            {--start=1 : Starting page number}
                            {--sync : Sync to local database}
                            {--with-servers : Also fetch video servers for each episode}
                            {--output-dir=scraper_output : Directory to save JSON files}
                            {--delay=1000 : Delay between requests in milliseconds}';

    protected $description = 'Batch scrape multiple animes from AnimeSail anime list';

    protected AnimeSailScraper $scraper;

    public function __construct()
    {
        parent::__construct();
        $this->scraper = new AnimeSailScraper();
    }

    public function handle()
    {
        $pages = (int) $this->option('pages');
        $startPage = (int) $this->option('start');
        $sync = $this->option('sync');
        $withServers = $this->option('with-servers');
        $outputDir = $this->option('output-dir');
        $delay = (int) $this->option('delay');

        // Create output directory
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->info("=== AnimeSail Batch Scraper ===");
        $this->line("Pages: {$startPage} to " . ($startPage + $pages - 1));
        $this->line("With servers: " . ($withServers ? 'Yes' : 'No'));
        $this->line("Sync to DB: " . ($sync ? 'Yes' : 'No'));
        $this->line('');

        $totalAnimes = 0;
        $totalEpisodes = 0;
        $totalServers = 0;

        for ($page = $startPage; $page < $startPage + $pages; $page++) {
            $this->info("--- Page {$page} ---");

            $listResult = $this->scraper->fetchAnimeListPage($page);

            if (!$listResult['success']) {
                $this->error("Failed to fetch page {$page}: " . ($listResult['error'] ?? 'Unknown error'));
                continue;
            }

            $this->line("Found {$listResult['count']} animes on page {$page}");

            foreach ($listResult['animes'] as $anime) {
                $this->line('');
                $this->info("Processing: {$anime['title']}");

                // Fetch episode list
                $episodeResult = $this->scraper->fetchEpisodeList($anime['url']);

                if (!$episodeResult['success']) {
                    $this->warn("  Failed to fetch episodes: " . ($episodeResult['error'] ?? 'Unknown'));
                    continue;
                }

                $this->line("  Episodes: " . $episodeResult['count']);
                $totalEpisodes += $episodeResult['count'];

                $animeData = [
                    'url' => $anime['url'],
                    'slug' => $anime['slug'],
                    'info' => $episodeResult['anime_info'],
                    'episodes' => [],
                ];

                if ($withServers) {
                    $bar = $this->output->createProgressBar(count($episodeResult['episodes']));
                    $bar->setFormat('  Fetching servers: %current%/%max% [%bar%] %percent:3s%%');

                    foreach ($episodeResult['episodes'] as $episode) {
                        $serverResult = $this->scraper->fetchEpisodeServers($episode['url']);

                        if ($serverResult['success']) {
                            $validServers = [];
                            
                            foreach ($serverResult['servers'] as $server) {
                                // Fetch embed URL if needed
                                $embedUrl = $server['url'];
                                
                                if (empty($embedUrl) && !empty($server['post_id']) && !empty($server['nonce'])) {
                                    $embedUrl = $this->scraper->fetchServerEmbed(
                                        $server['post_id'],
                                        $server['type'],
                                        $server['nume'],
                                        $server['nonce'],
                                        $episode['url']
                                    );
                                    usleep($delay * 500); // Half delay for AJAX
                                }

                                if (!empty($embedUrl) && !$this->isInternalServer($embedUrl)) {
                                    $validServers[] = [
                                        'name' => $server['name'],
                                        'url' => $embedUrl,
                                        'type' => $server['type'] ?? 'unknown',
                                    ];
                                    $totalServers++;
                                }
                            }

                            $animeData['episodes'][] = [
                                'url' => $episode['url'],
                                'title' => $episode['title'],
                                'episode_number' => $episode['episode_number'],
                                'servers' => $validServers,
                            ];
                        }

                        $bar->advance();
                        usleep($delay * 1000);
                    }

                    $bar->finish();
                    $this->line('');
                } else {
                    $animeData['episodes'] = $episodeResult['episodes'];
                }

                // Save to file
                $filename = "{$outputDir}/{$anime['slug']}.json";
                $this->saveToFile($filename, $animeData);
                $this->line("  Saved: {$filename}");

                // Sync if requested
                if ($sync) {
                    $this->syncAnime($animeData);
                }

                $totalAnimes++;
                usleep($delay * 1000);
            }

            // Check if more pages exist
            if (!$listResult['has_next']) {
                $this->warn("No more pages available after page {$page}");
                break;
            }

            usleep($delay * 1000);
        }

        $this->line('');
        $this->info("=== Batch Scraping Complete ===");
        $this->line("Total animes: {$totalAnimes}");
        $this->line("Total episodes: {$totalEpisodes}");
        $this->line("Total servers: {$totalServers}");
        $this->line("Output directory: {$outputDir}");

        return 0;
    }

    /**
     * Check if URL is internal AnimeSail server
     */
    protected function isInternalServer(string $url): bool
    {
        $patterns = [
            '154.26.137.28',
            '185.217.95.',
            'nontonanimeid',
            'animesail',
            '/proxy/',
            '/embed-local/',
        ];

        foreach ($patterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sync anime data to database
     */
    protected function syncAnime(array $animeData): void
    {
        // Implementation similar to ScrapeAnimeSailCommand::syncToDatabase
        // This would match local anime and sync episodes/servers
    }

    /**
     * Save data to JSON file
     */
    protected function saveToFile(string $filename, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filename, $json);
    }
}
