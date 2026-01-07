<?php

namespace App\Console\Commands;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\VideoServer;
use App\Services\AnimeSailScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScrapeAnimeSailCommand extends Command
{
    protected $signature = 'scrape:animesail 
                            {--anime= : Specific anime URL to scrape}
                            {--slug= : Anime slug to match with local database}
                            {--sync : Sync servers to local database}
                            {--list-only : Only fetch episode list, no servers}
                            {--limit=0 : Limit number of episodes to scrape (0 = all)}
                            {--delay=500 : Delay between requests in milliseconds}
                            {--output= : Save results to JSON file}';

    protected $description = 'Scrape anime episodes and video servers from AnimeSail';

    protected AnimeSailScraper $scraper;

    public function __construct()
    {
        parent::__construct();
        $this->scraper = new AnimeSailScraper();
    }

    public function handle()
    {
        $animeUrl = $this->option('anime');
        $slug = $this->option('slug');
        $sync = $this->option('sync');
        $listOnly = $this->option('list-only');
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $outputFile = $this->option('output');

        if (!$animeUrl && !$slug) {
            $this->error('Please provide --anime=URL or --slug=anime-slug');
            $this->line('');
            $this->line('Examples:');
            $this->line('  php artisan scrape:animesail --anime=https://154.26.137.28/anime/one-piece/');
            $this->line('  php artisan scrape:animesail --slug=one-piece --sync');
            $this->line('  php artisan scrape:animesail --anime=https://154.26.137.28/anime/one-piece/ --list-only');
            return 1;
        }

        // Build URL from slug if not provided
        if (!$animeUrl && $slug) {
            $animeUrl = "https://154.26.137.28/anime/{$slug}/";
        }

        $this->info("Scraping: {$animeUrl}");
        $this->line('');

        // Step 1: Fetch episode list
        $this->info('Fetching episode list...');
        $episodeList = $this->scraper->fetchEpisodeList($animeUrl);

        if (!$episodeList['success']) {
            $this->error("Failed: " . ($episodeList['error'] ?? 'Unknown error'));
            return 1;
        }

        $animeInfo = $episodeList['anime_info'];
        $episodes = $episodeList['episodes'];

        $this->info("Anime: {$animeInfo['title']}");
        $this->info("Type: {$animeInfo['type']}");
        $this->info("Status: {$animeInfo['status']}");
        $this->info("Episodes found: " . count($episodes));
        $this->line('');

        if ($listOnly) {
            $this->table(
                ['#', 'Episode', 'URL'],
                collect($episodes)->map(fn($ep, $i) => [
                    $i + 1,
                    $ep['title'],
                    $ep['url']
                ])->toArray()
            );

            if ($outputFile) {
                $this->saveToFile($outputFile, [
                    'anime' => $animeInfo,
                    'episodes' => $episodes,
                ]);
            }

            return 0;
        }

        // Apply limit
        if ($limit > 0 && $limit < count($episodes)) {
            $episodes = array_slice($episodes, 0, $limit);
            $this->warn("Limited to {$limit} episodes");
        }

        // Step 2: Fetch servers for each episode
        $bar = $this->output->createProgressBar(count($episodes));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        $results = [
            'anime' => $animeInfo,
            'episodes' => [],
            'total_servers' => 0,
            'skipped_internal' => 0,
        ];

        foreach ($episodes as $index => $episode) {
            $bar->setMessage("Episode " . ($episode['episode_number'] ?? ($index + 1)));

            $serverResult = $this->scraper->fetchEpisodeServers($episode['url']);

            if ($serverResult['success']) {
                $servers = $serverResult['servers'];
                
                // Fetch actual embed URLs via AJAX
                $validServers = [];
                foreach ($servers as $i => $server) {
                    // Skip servers without AJAX data
                    if (empty($server['post_id']) && empty($server['url'])) {
                        continue;
                    }

                    $embedUrl = $server['url'];
                    
                    // If no URL yet, fetch via AJAX
                    if (empty($embedUrl) && !empty($server['post_id']) && !empty($server['nonce'])) {
                        $embedUrl = $this->scraper->fetchServerEmbed(
                            $server['post_id'],
                            $server['type'],
                            $server['nume'],
                            $server['nonce'],
                            $episode['url']
                        );
                        usleep($delay * 1000);
                    }

                    if (empty($embedUrl)) {
                        continue;
                    }

                    // Skip internal servers
                    if ($this->isInternalServer($embedUrl)) {
                        $results['skipped_internal']++;
                        continue;
                    }

                    $validServers[] = [
                        'name' => $server['name'],
                        'url' => $embedUrl,
                        'type' => $server['type'] ?? 'unknown',
                    ];
                }

                $results['episodes'][] = [
                    'url' => $episode['url'],
                    'title' => $episode['title'],
                    'episode_number' => $episode['episode_number'],
                    'servers' => $validServers,
                    'server_count' => count($validServers),
                ];
                
                $results['total_servers'] += count($validServers);

                // Sync to database if requested
                if ($sync && !empty($validServers)) {
                    $this->syncToDatabase($animeInfo['title'], $episode, $validServers);
                }
            }

            $bar->advance();
            usleep($delay * 1000);
        }

        $bar->setMessage('Done!');
        $bar->finish();
        $this->line('');
        $this->line('');

        // Summary
        $this->info('=== Summary ===');
        $this->line("Episodes processed: " . count($results['episodes']));
        $this->line("Total servers found: " . $results['total_servers']);
        $this->line("Internal servers skipped: " . $results['skipped_internal']);

        if ($outputFile) {
            $this->saveToFile($outputFile, $results);
            $this->info("Results saved to: {$outputFile}");
        }

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
     * Sync servers to local database
     */
    protected function syncToDatabase(string $animeTitle, array $episode, array $servers): void
    {
        // Find matching anime
        $anime = Anime::where('title', 'LIKE', '%' . substr($animeTitle, 0, 50) . '%')->first();
        
        if (!$anime) {
            return;
        }

        // Find matching episode
        $ep = Episode::where('anime_id', $anime->id)
            ->where('episode_number', $episode['episode_number'])
            ->first();

        if (!$ep) {
            return;
        }

        // Sync servers
        foreach ($servers as $server) {
            if (empty($server['url'])) {
                continue;
            }

            VideoServer::updateOrCreate(
                [
                    'episode_id' => $ep->id,
                    'embed_url' => $server['url'],
                ],
                [
                    'server_name' => $server['name'],
                    'is_active' => true,
                    'source' => 'sync',
                ]
            );
        }
    }

    /**
     * Save results to JSON file
     */
    protected function saveToFile(string $filename, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filename, $json);
    }
}
