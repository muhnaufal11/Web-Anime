<?php

namespace App\Jobs;

use App\Services\AnimeSailScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ScrapeAnimeSailBulkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public array $options;

    protected AnimeSailScraper $scraper;
    protected string $cacheKey;

    public function __construct(int $userId, array $options = [])
    {
        $this->userId = $userId;
        $this->options = $options;
        $this->scraper = new AnimeSailScraper();
        $this->cacheKey = $this->options['cache_key'] ?? ('animesail_bulk_scrape:' . $this->userId . ':' . (int) (microtime(true)));
    }

    public function handle()
    {
        Cache::put($this->cacheKey, [
            'status' => 'running',
            'progress' => 0,
            'logs' => [['time' => now()->format('H:i:s'), 'message' => 'Starting bulk scrape...']],
            'results' => [],
            'output_dir' => $this->options['output_dir'] ?? 'scraper_output',
        ], now()->addHours(3));

        $mode = $this->options['mode'] ?? 'pages';
        $delay = (int) ($this->options['delay'] ?? 1000);
        $withServers = (bool) ($this->options['with_servers'] ?? false);
        $sync = (bool) ($this->options['sync'] ?? false);
        $outputDir = $this->options['output_dir'] ?? ('scraper_output_' . date('Ymd_His'));

        try {
            if (!is_dir(storage_path('app/' . $outputDir))) {
                Storage::makeDirectory($outputDir);
            }

            $items = [];

            if ($mode === 'pages') {
                $pages = (int) ($this->options['pages'] ?? 1);
                $start = (int) ($this->options['start'] ?? 1);

                for ($page = $start; $page < $start + $pages; $page++) {
                    $this->log("Fetching list page {$page}...");
                    $listResult = $this->scraper->fetchAnimeListPage($page);

                    if (!$listResult['success']) {
                        $this->log("Failed to fetch page {$page}: " . ($listResult['error'] ?? 'Unknown'));
                        continue;
                    }

                    foreach ($listResult['animes'] as $anime) {
                        $items[] = $anime['url'];
                    }

                    usleep($delay * 1000);
                }
            } else {
                // mode = urls
                $raw = $this->options['urls'] ?? '';
                $lines = preg_split('/\r?\n/', trim($raw));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $items[] = $line;
                    }
                }
            }

            $total = count($items);
            $processed = 0;
            $results = [];

            foreach ($items as $itemUrl) {
                $processed++;
                $this->log("Processing: {$itemUrl} ({$processed}/{$total})");

                // Fetch episode list
                $episodeResult = $this->scraper->fetchEpisodeList($itemUrl);
                if (!$episodeResult['success']) {
                    $this->log("  Failed to fetch episodes: " . ($episodeResult['error'] ?? 'Unknown'));
                    continue;
                }

                $animeData = [
                    'url' => $itemUrl,
                    'slug' => $episodeResult['anime_info']['slug'] ?? null,
                    'info' => $episodeResult['anime_info'] ?? null,
                    'episodes' => [],
                ];

                if ($withServers) {
                    foreach ($episodeResult['episodes'] as $episode) {
                        $serverResult = $this->scraper->fetchEpisodeServers($episode['url']);

                        if ($serverResult['success']) {
                            $validServers = [];
                            foreach ($serverResult['servers'] as $server) {
                                $embedUrl = $server['url'] ?? null;
                                if (empty($embedUrl) && !empty($server['post_id'])) {
                                    $embedUrl = $this->scraper->fetchServerEmbed(
                                        $server['post_id'],
                                        $server['type'],
                                        $server['nume'] ?? null,
                                        $server['nonce'] ?? null,
                                        $episode['url']
                                    );
                                    usleep($delay * 500);
                                }

                                if (!empty($embedUrl) && !$this->isInternalServer($embedUrl)) {
                                    $validServers[] = ['name' => $server['name'], 'url' => $embedUrl];
                                }
                            }

                            $animeData['episodes'][] = [
                                'url' => $episode['url'],
                                'title' => $episode['title'] ?? null,
                                'episode_number' => $episode['episode_number'] ?? null,
                                'servers' => $validServers,
                            ];
                        }

                        usleep($delay * 1000);
                    }
                } else {
                    $animeData['episodes'] = $episodeResult['episodes'];
                }

                $filename = $outputDir . '/' . ($animeData['slug'] ?? md5($itemUrl)) . '.json';
                Storage::put($filename, json_encode($animeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $this->log("  Saved: {$filename}");

                if ($sync) {
                    // Sync logic can be implemented here
                    $this->log('  Sync to DB requested (not implemented in job)');
                }

                $results[] = $animeData;

                $percent = (int) ($processed / $total * 100);
                Cache::put($this->cacheKey, ['status' => 'running', 'progress' => $percent, 'logs' => $this->getLogs(), 'results' => $results, 'output_dir' => $outputDir], now()->addHours(3));
            }

            Cache::put($this->cacheKey, ['status' => 'done', 'progress' => 100, 'logs' => $this->getLogs(), 'results' => $results, 'output_dir' => $outputDir], now()->addHours(3));

        } catch (\Exception $e) {
            Log::error('ScrapeAnimeSailBulkJob error: ' . $e->getMessage());
            Cache::put($this->cacheKey, ['status' => 'error', 'progress' => 0, 'logs' => $this->getLogsWithMessage('Error: ' . $e->getMessage()), 'results' => [], 'output_dir' => $outputDir, 'error' => $e->getMessage()], now()->addHours(3));
        }
    }

    protected array $internalLogs = [];

    protected function log(string $message): void
    {
        $this->internalLogs[] = ['time' => now()->format('H:i:s'), 'message' => $message];
    }

    protected function getLogs(): array
    {
        return $this->internalLogs;
    }

    protected function getLogsWithMessage(string $message): array
    {
        $l = $this->getLogs();
        $l[] = ['time' => now()->format('H:i:s'), 'message' => $message];
        return $l;
    }

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

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }
}
