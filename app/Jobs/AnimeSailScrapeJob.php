<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\VideoServer;
use App\Services\AnimeSailScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnimeSailScrapeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes
    public int $tries = 1;

    protected int $userId;
    protected array $options;

    public function __construct(int $userId, array $options)
    {
        $this->userId = $userId;
        $this->options = $options;
    }

    public function handle()
    {
        $cacheKey = 'animesail_scrape:' . $this->userId;
        
        try {
            $scraper = new AnimeSailScraper();
            
            $scrapeType = $this->options['scrapeType'] ?? 'anime';
            $animeUrl = $this->options['animeUrl'] ?? '';
            $localAnimeId = $this->options['localAnimeId'] ?? null;
            $limit = $this->options['limit'] ?? 0;
            $delay = $this->options['delay'] ?? 500;
            $syncToDatabase = $this->options['syncToDatabase'] ?? false;
            $fetchServers = $this->options['fetchServers'] ?? true;

            if ($scrapeType === 'episode') {
                $this->scrapeEpisode($scraper, $animeUrl, $localAnimeId, $syncToDatabase, $fetchServers, $delay, $cacheKey);
            } else {
                $this->scrapeAnime($scraper, $animeUrl, $localAnimeId, $limit, $syncToDatabase, $fetchServers, $delay, $cacheKey);
            }

        } catch (\Exception $e) {
            Log::error("AnimeSailScrapeJob error: " . $e->getMessage());
            $this->updateCache($cacheKey, [
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function scrapeEpisode($scraper, $url, $localAnimeId, $sync, $fetchServers, $delay, $cacheKey)
    {
        $this->addLog($cacheKey, "🎬 Fetching episode: {$url}");
        
        $result = $scraper->fetchEpisodeServers($url);
        
        if (!$result['success']) {
            $this->addLog($cacheKey, "❌ Error: " . ($result['error'] ?? 'Unknown'));
            $this->updateCache($cacheKey, ['status' => 'error', 'error' => $result['error']]);
            return;
        }

        $this->addLog($cacheKey, "✅ Found {$result['count']} servers");
        
        $validServers = [];
        foreach ($result['servers'] as $server) {
            $embedUrl = $server['url'] ?? null;
            
            if ($fetchServers && empty($embedUrl) && !empty($server['post_id'])) {
                $this->addLog($cacheKey, "  📡 Fetching {$server['name']}...");
                $embedUrl = $scraper->fetchServerEmbed(
                    $server['post_id'],
                    $server['type'],
                    $server['nume'],
                    $server['nonce'] ?? '',
                    $url
                );
                usleep($delay * 1000);
            }
            
            if (!empty($embedUrl) && !$this->isInternalServer($embedUrl)) {
                $validServers[] = [
                    'name' => $server['name'],
                    'url' => $embedUrl,
                    'type' => $server['type'] ?? 'unknown',
                ];
            }
        }

        if ($sync && $localAnimeId && !empty($validServers)) {
            $this->syncServers($validServers, $localAnimeId, null, $cacheKey);
        }

        $this->addLog($cacheKey, "✅ Complete! Found " . count($validServers) . " valid servers");
        
        $this->updateCache($cacheKey, [
            'status' => 'done',
            'progress' => 100,
            'results' => [
                'type' => 'episode',
                'url' => $url,
                'servers' => $validServers,
                'total' => count($validServers),
            ],
        ]);
    }

    protected function scrapeAnime($scraper, $url, $localAnimeId, $limit, $sync, $fetchServers, $delay, $cacheKey)
    {
        $this->addLog($cacheKey, "📺 Fetching anime: {$url}");
        
        $episodeResult = $scraper->fetchEpisodeList($url);
        if (!$episodeResult['success']) {
            $this->addLog($cacheKey, "❌ Error: " . ($episodeResult['error'] ?? 'Unknown'));
            $this->updateCache($cacheKey, ['status' => 'error', 'error' => $episodeResult['error']]);
            return;
        }

        $animeInfo = $episodeResult['anime_info'];
        $episodes = $episodeResult['episodes'];
        
        $this->addLog($cacheKey, "✅ Found " . count($episodes) . " episodes");
        $this->addLog($cacheKey, "📺 Anime: " . ($animeInfo['title'] ?? 'Unknown'));

        if ($limit > 0 && $limit < count($episodes)) {
            $episodes = array_slice($episodes, 0, $limit);
            $this->addLog($cacheKey, "⚠️ Limited to {$limit} episodes");
        }

        $allServers = [];
        $totalServers = 0;
        $total = count($episodes);

        foreach ($episodes as $index => $episode) {
            $progress = (int) (($index + 1) / $total * 100);
            $this->updateCache($cacheKey, ['progress' => $progress]);
            
            $this->addLog($cacheKey, "🎬 Episode " . ($episode['episode_number'] ?? ($index + 1)) . "...");
            
            $serverResult = $scraper->fetchEpisodeServers($episode['url']);
            
            if ($serverResult['success']) {
                $validServers = [];
                
                foreach ($serverResult['servers'] as $server) {
                    $embedUrl = $server['url'] ?? null;
                    
                    if ($fetchServers && empty($embedUrl) && !empty($server['post_id'])) {
                        $embedUrl = $scraper->fetchServerEmbed(
                            $server['post_id'],
                            $server['type'],
                            $server['nume'],
                            $server['nonce'] ?? '',
                            $episode['url']
                        );
                        usleep(($delay / 2) * 1000);
                    }
                    
                    if (!empty($embedUrl) && !$this->isInternalServer($embedUrl)) {
                        $validServers[] = [
                            'name' => $server['name'],
                            'url' => $embedUrl,
                        ];
                        $totalServers++;
                    }
                }
                
                $allServers[] = [
                    'episode' => $episode['episode_number'] ?? ($index + 1),
                    'title' => $episode['title'],
                    'url' => $episode['url'],
                    'servers' => $validServers,
                ];
                
                $this->addLog($cacheKey, "  ✅ " . count($validServers) . " servers");
                
                if ($sync && $localAnimeId && !empty($validServers)) {
                    $this->syncServers($validServers, $localAnimeId, $episode['episode_number'], $cacheKey);
                }
            }
            
            usleep($delay * 1000);
        }

        $this->addLog($cacheKey, "✅ Complete! {$totalServers} servers from " . count($allServers) . " episodes");
        
        $this->updateCache($cacheKey, [
            'status' => 'done',
            'progress' => 100,
            'results' => [
                'type' => 'anime',
                'anime' => $animeInfo,
                'episodes' => $allServers,
                'total_episodes' => count($allServers),
                'total_servers' => $totalServers,
            ],
        ]);
    }

    protected function isInternalServer(string $url): bool
    {
        $patterns = ['154.26.137.28', '185.217.95.', 'nontonanimeid', 'animesail', '/proxy/', '/embed-local/'];
        foreach ($patterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function syncServers(array $servers, int $animeId, ?int $episodeNumber, string $cacheKey): void
    {
        $query = Episode::where('anime_id', $animeId);
        if ($episodeNumber) {
            $query->where('episode_number', $episodeNumber);
        }
        
        $episode = $query->first();
        if (!$episode) {
            $this->addLog($cacheKey, "  ⚠️ Episode not found in DB");
            return;
        }

        $created = 0;
        foreach ($servers as $server) {
            if (empty($server['url'])) continue;

            $vs = VideoServer::updateOrCreate(
                ['episode_id' => $episode->id, 'embed_url' => $server['url']],
                ['server_name' => $server['name'], 'is_active' => true, 'source' => 'sync']
            );
            
            if ($vs->wasRecentlyCreated) $created++;
        }
        
        if ($created > 0) {
            $this->addLog($cacheKey, "  💾 Saved {$created} servers to DB");
        }
    }

    protected function addLog(string $cacheKey, string $message): void
    {
        $state = Cache::get($cacheKey, ['logs' => []]);
        $state['logs'][] = ['time' => now()->format('H:i:s'), 'message' => $message];
        Cache::put($cacheKey, $state, now()->addMinutes(30));
    }

    protected function updateCache(string $cacheKey, array $data): void
    {
        $state = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_merge($state, $data), now()->addMinutes(30));
    }
}
