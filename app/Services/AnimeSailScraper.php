<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnimeSailScraper
{
    protected string $baseUrl = 'https://154.26.137.28';
    protected string $alternativeUrl = 'https://animesail.org'; // Alternative domain
    
    protected string $storageDir = 'scraper_cache';
    
    /**
     * Required cookies to bypass AnimeSail's anti-bot protection
     */
    protected function getRequiredCookies(): string
    {
        $tz = 'Asia/Jakarta';
        $locale = 'id-ID';
        return "_as_ipin_ct=ID; _as_ipin_tz={$tz}; _as_ipin_lc={$locale}";
    }
    
    /**
     * Make HTTP request with required cookies
     */
    protected function makeRequest(string $url): ?\Illuminate\Http\Client\Response
    {
        return Http::timeout(30)
            ->withHeaders($this->getHeaders())
            ->withHeaders(['Cookie' => $this->getRequiredCookies()])
            ->get($url);
    }
    
    /**
     * Fetch anime list page
     */
    public function fetchAnimeListPage(int $page = 1): array
    {
        try {
            $url = "{$this->baseUrl}/anime/page/{$page}/";
            if ($page === 1) {
                $url = "{$this->baseUrl}/anime/";
            }
            
            $response = $this->makeRequest($url);
            
            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Failed to fetch page: ' . $response->status()];
            }
            
            $html = $response->body();
            $animes = $this->extractAnimeList($html);
            
            // Check if there's next page
            $hasNextPage = str_contains($html, 'class="next page-numbers"');
            
            return [
                'success' => true,
                'page' => $page,
                'animes' => $animes,
                'count' => count($animes),
                'has_next' => $hasNextPage,
            ];
            
        } catch (\Exception $e) {
            Log::error("AnimeSailScraper List Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Extract anime list from HTML
     */
    protected function extractAnimeList(string $html): array
    {
        $animes = [];
        
        // Pattern untuk anime list item
        // <article class="bs">...<a href="URL" title="TITLE">...</article>
        preg_match_all('/<article[^>]*class="[^"]*bs[^"]*"[^>]*>.*?<a\s+href="([^"]+)"[^>]*title="([^"]+)".*?<\/article>/is', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $url = $match[1];
            $title = html_entity_decode(trim($match[2]));
            
            // Extract slug from URL
            $slug = '';
            if (preg_match('/\/anime\/([^\/]+)\/?$/', $url, $slugMatch)) {
                $slug = $slugMatch[1];
            }
            
            $animes[] = [
                'url' => $url,
                'title' => $title,
                'slug' => $slug,
            ];
        }
        
        // Alternative pattern (serieslist)
        if (empty($animes)) {
            preg_match_all('/<div class="bsx">\s*<a\s+href="([^"]+)"[^>]*title="([^"]+)"/is', $html, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $url = $match[1];
                $title = html_entity_decode(trim($match[2]));
                
                $slug = '';
                if (preg_match('/\/anime\/([^\/]+)\/?$/', $url, $slugMatch)) {
                    $slug = $slugMatch[1];
                }
                
                $animes[] = [
                    'url' => $url,
                    'title' => $title,
                    'slug' => $slug,
                ];
            }
        }
        
        return $animes;
    }
    
    /**
     * Fetch episode list from anime detail page
     */
    public function fetchEpisodeList(string $animeUrl): array
    {
        try {
            $response = $this->makeRequest($animeUrl);
            
            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Failed to fetch anime page: ' . $response->status()];
            }
            
            $html = $response->body();
            
            // Check if we got the real page or loading page
            if (str_contains($html, '<title>Loading..</title>')) {
                return ['success' => false, 'error' => 'Got loading page - anti-bot protection active'];
            }
            
            $episodes = $this->extractEpisodeList($html);
            $animeInfo = $this->extractAnimeInfo($html);
            
            return [
                'success' => true,
                'anime_url' => $animeUrl,
                'anime_info' => $animeInfo,
                'episodes' => $episodes,
                'count' => count($episodes),
            ];
            
        } catch (\Exception $e) {
            Log::error("AnimeSailScraper Episode List Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Extract episode list from anime detail HTML
     */
    protected function extractEpisodeList(string $html): array
    {
        $episodes = [];
        
        // Pattern: <ul class="daftar"><li><a href="URL">Title</a></li>...
        preg_match_all('/<ul[^>]*class="[^"]*daftar[^"]*"[^>]*>(.*?)<\/ul>/is', $html, $listMatches);
        
        if (!empty($listMatches[1])) {
            foreach ($listMatches[1] as $listHtml) {
                preg_match_all('/<li>\s*<a\s+href="([^"]+)"[^>]*>([^<]+)<\/a>/is', $listHtml, $matches, PREG_SET_ORDER);
                
                foreach ($matches as $match) {
                    $url = trim($match[1]);
                    $title = html_entity_decode(trim($match[2]));
                    
                    // Extract episode number
                    $episodeNum = null;
                    if (preg_match('/Episode\s*(\d+)/i', $title, $epMatch)) {
                        $episodeNum = (int) $epMatch[1];
                    }
                    
                    $episodes[] = [
                        'url' => $url,
                        'title' => $title,
                        'episode_number' => $episodeNum,
                    ];
                }
            }
        }
        
        // Alternative pattern - episode list in different structure
        if (empty($episodes)) {
            preg_match_all('/<a[^>]+href="([^"]*episode[^"]*)"[^>]*>([^<]*Episode[^<]*)<\/a>/is', $html, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $url = trim($match[1]);
                $title = html_entity_decode(trim($match[2]));
                
                $episodeNum = null;
                if (preg_match('/Episode\s*(\d+)/i', $title, $epMatch)) {
                    $episodeNum = (int) $epMatch[1];
                }
                
                $episodes[] = [
                    'url' => $url,
                    'title' => $title,
                    'episode_number' => $episodeNum,
                ];
            }
        }
        
        return $episodes;
    }
    
    /**
     * Extract anime info from detail page
     */
    protected function extractAnimeInfo(string $html): array
    {
        $info = [
            'title' => '',
            'alternative' => '',
            'type' => '',
            'status' => '',
            'genres' => [],
        ];
        
        // Title
        if (preg_match('/<h1[^>]*class="[^"]*entry-title[^"]*"[^>]*>([^<]+)/i', $html, $m)) {
            $info['title'] = html_entity_decode(trim($m[1]));
        }
        
        // Alternative title
        if (preg_match('/<th>Alternatif:<\/th>\s*<td>([^<]+)/i', $html, $m)) {
            $info['alternative'] = html_entity_decode(trim($m[1]));
        }
        
        // Type
        if (preg_match('/<th>Tipe:<\/th>\s*<td>([^<]+)/i', $html, $m)) {
            $info['type'] = trim($m[1]);
        }
        
        // Status
        if (preg_match('/<th>Status:<\/th>\s*<td>([^<]+)/i', $html, $m)) {
            $info['status'] = trim($m[1]);
        }
        
        // Genres
        if (preg_match('/<th>Genre:<\/th>\s*<td>(.*?)<\/td>/is', $html, $m)) {
            preg_match_all('/rel="tag">([^<]+)/i', $m[1], $genreMatches);
            $info['genres'] = $genreMatches[1] ?? [];
        }
        
        return $info;
    }
    
    /**
     * Fetch episode page and extract video servers
     */
    public function fetchEpisodeServers(string $episodeUrl): array
    {
        try {
            $response = $this->makeRequest($episodeUrl);
            
            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Failed to fetch episode: ' . $response->status()];
            }
            
            $html = $response->body();
            
            // Check if we got the real page or loading page
            if (str_contains($html, '<title>Loading..</title>')) {
                return ['success' => false, 'error' => 'Got loading page - anti-bot protection active'];
            }
            
            // Save HTML for later processing if needed
            $this->cacheHtml($episodeUrl, $html);
            
            // Extract servers using same logic as NontonAnimeIdFetcher
            $servers = $this->extractServersFromHtml($html, $episodeUrl);
            
            return [
                'success' => true,
                'episode_url' => $episodeUrl,
                'servers' => $servers,
                'count' => count($servers),
                'html_cached' => true,
            ];
            
        } catch (\Exception $e) {
            Log::error("AnimeSailScraper Episode Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Extract servers from episode HTML
     */
    protected function extractServersFromHtml(string $html, string $episodeUrl): array
    {
        $servers = [];
        
        // Extract page data (post_id, nonce, etc)
        $postId = null;
        $nonce = null;
        
        // Extract nonce from script
        if (preg_match('/var\s+kotakajax\s*=\s*\{[^}]*"nonce"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $nonce = $m[1];
        }
        
        // Try from base64 script
        if (empty($nonce)) {
            if (preg_match('/src="data:text\/javascript;base64,([^"]+)"[^>]*id="ajax_video-js-extra"/', $html, $b64Match)) {
                $decoded = base64_decode($b64Match[1]);
                if (preg_match('/"nonce"\s*:\s*"([^"]+)"/', $decoded, $nonceMatch)) {
                    $nonce = $nonceMatch[1];
                }
            }
        }
        
        // METHOD 1: Extract from data-default attribute (base64 encoded iframe)
        if (preg_match('/data-default="([^"]+)"/i', $html, $defaultMatch)) {
            $decoded = base64_decode($defaultMatch[1]);
            if (preg_match('/src="([^"]+)"/i', $decoded, $srcMatch)) {
                $url = $srcMatch[1];
                if (!empty($url) && !$this->isInternalServerUrl($url)) {
                    $servers[] = [
                        'name' => $this->guessServerName($url),
                        'url' => $url,
                        'type' => 'default',
                    ];
                }
            }
        }
        
        // METHOD 2: Extract from select.mirror options with data-em attribute
        preg_match_all('/<option[^>]*data-em="([^"]+)"[^>]*>([^<]+)<\/option>/i', $html, $optionMatches, PREG_SET_ORDER);
        foreach ($optionMatches as $opt) {
            $b64 = $opt[1];
            $name = trim($opt[2]);
            
            // Decode base64
            $decoded = base64_decode($b64, true);
            if ($decoded && preg_match('/src="([^"]+)"/i', $decoded, $srcMatch)) {
                $url = $srcMatch[1];
                if (!empty($url) && !$this->isInternalServerUrl($url)) {
                    // Check for duplicates
                    $exists = false;
                    foreach ($servers as $s) {
                        if (($s['url'] ?? '') === $url) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $servers[] = [
                            'name' => $name ?: $this->guessServerName($url),
                            'url' => $url,
                            'type' => 'mirror',
                        ];
                    }
                }
            }
        }
        
        // METHOD 2b: Also try value attribute for backwards compatibility
        preg_match_all('/<option[^>]*value="([A-Za-z0-9+\/=]{20,})"[^>]*>([^<]+)<\/option>/i', $html, $optionMatches, PREG_SET_ORDER);
        foreach ($optionMatches as $opt) {
            $value = $opt[1];
            $name = trim($opt[2]);
            
            // Check if value is base64
            $decoded = base64_decode($value, true);
            if ($decoded && preg_match('/src="([^"]+)"/i', $decoded, $srcMatch)) {
                $url = $srcMatch[1];
                if (!empty($url) && !$this->isInternalServerUrl($url)) {
                    $exists = false;
                    foreach ($servers as $s) {
                        if (($s['url'] ?? '') === $url) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $servers[] = [
                            'name' => $name ?: $this->guessServerName($url),
                            'url' => $url,
                            'type' => 'mirror',
                        ];
                    }
                }
            }
        }
        
        // METHOD 3: Extract servers from player tabs (li elements with data attributes)
        preg_match_all('/<li[^>]*id="player-option-(\d+)"[^>]*data-post="(\d+)"[^>]*data-type="([^"]+)"[^>]*data-nume="(\d+)"[^>]*>.*?<span>([^<]+)<\/span>/is', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            if (empty($postId)) {
                $postId = $match[2];
            }
            
            $servers[] = [
                'option_id' => (int) $match[1],
                'post_id' => $match[2],
                'type' => $match[3],
                'nume' => (int) $match[4],
                'name' => trim(preg_replace('/^S-/i', '', $match[5])),
                'url' => '', // Will need AJAX to get URL
                'nonce' => $nonce,
            ];
        }
        
        // METHOD 4: Extract first server's iframe URL directly from visible iframe
        if (preg_match('/<iframe[^>]*(?:data-src|src)="([^"]+)"[^>]*>/i', $html, $iframeMatch)) {
            $url = $iframeMatch[1];
            if (!empty($url) && !$this->isInternalServerUrl($url)) {
                // Check if already added
                $exists = false;
                foreach ($servers as $s) {
                    if (($s['url'] ?? '') === $url) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    array_unshift($servers, [
                        'name' => $this->guessServerName($url),
                        'url' => $url,
                        'type' => 'iframe',
                    ]);
                }
            }
        }
        
        // METHOD 5: Extract from load_embed() calls in script
        preg_match_all('/load_embed\s*\(\s*["\']([^"\']+)["\']\s*\)/i', $html, $loadMatches);
        foreach ($loadMatches[1] as $b64) {
            $decoded = base64_decode($b64, true);
            if ($decoded && preg_match('/src="([^"]+)"/i', $decoded, $srcMatch)) {
                $url = $srcMatch[1];
                if (!empty($url) && !$this->isInternalServerUrl($url)) {
                    $exists = false;
                    foreach ($servers as $s) {
                        if (($s['url'] ?? '') === $url) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $servers[] = [
                            'name' => $this->guessServerName($url),
                            'url' => $url,
                            'type' => 'embed',
                        ];
                    }
                }
            }
        }
        
        // Try alternative download links
        preg_match_all('/<a[^>]+href="([^"]+)"[^>]*class="[^"]*download[^"]*"[^>]*>([^<]+)/i', $html, $dlMatches, PREG_SET_ORDER);
        foreach ($dlMatches as $dlMatch) {
            $servers[] = [
                'type' => 'download',
                'name' => trim($dlMatch[2]),
                'url' => $dlMatch[1],
            ];
        }
        
        return $servers;
    }
    
    /**
     * Fetch embed URL via AJAX (same as NontonAnimeIdFetcher)
     */
    public function fetchServerEmbed(string $postId, string $serverType, int $nume, string $nonce, string $referer): ?string
    {
        try {
            $ajaxUrl = "{$this->baseUrl}/wp-admin/admin-ajax.php";
            
            $response = Http::asForm()
                ->timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => '*/*',
                    'Accept-Language' => 'en-US,en;q=0.9,id;q=0.8',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Referer' => $referer,
                    'Origin' => $this->baseUrl,
                ])
                ->post($ajaxUrl, [
                    'action' => 'doo_player_ajax',
                    'post' => $postId,
                    'nume' => $nume,
                    'type' => $serverType,
                ]);
            
            if (!$response->successful()) {
                return null;
            }
            
            $body = $response->body();
            
            $json = json_decode($body, true);
            
            if (isset($json['embed_url'])) {
                return $json['embed_url'];
            }
            
            // Try to extract from HTML response
            if (preg_match('/<iframe[^>]*src="([^"]+)"/i', $body, $m)) {
                return $m[1];
            }
            
            if (preg_match('/"embed_url"\s*:\s*"([^"]+)"/', $body, $m)) {
                return stripcslashes($m[1]);
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("AJAX fetch error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Full scrape: Get anime, all episodes, and all servers
     */
    public function scrapeAnimeComplete(string $animeUrl, callable $progressCallback = null): array
    {
        $result = [
            'success' => false,
            'anime' => null,
            'episodes' => [],
            'total_servers' => 0,
            'errors' => [],
        ];
        
        // Step 1: Fetch episode list
        $episodeList = $this->fetchEpisodeList($animeUrl);
        if (!$episodeList['success']) {
            $result['errors'][] = "Failed to fetch episode list: " . ($episodeList['error'] ?? 'Unknown error');
            return $result;
        }
        
        $result['anime'] = $episodeList['anime_info'];
        
        if ($progressCallback) {
            $progressCallback("Found {$episodeList['count']} episodes for {$episodeList['anime_info']['title']}");
        }
        
        // Step 2: Fetch each episode's servers
        foreach ($episodeList['episodes'] as $index => $episode) {
            if ($progressCallback) {
                $progressCallback("Processing episode " . ($index + 1) . "/{$episodeList['count']}: {$episode['title']}");
            }
            
            $serverResult = $this->fetchEpisodeServers($episode['url']);
            
            if ($serverResult['success']) {
                $result['episodes'][] = [
                    'url' => $episode['url'],
                    'title' => $episode['title'],
                    'episode_number' => $episode['episode_number'],
                    'servers' => $serverResult['servers'],
                    'server_count' => count($serverResult['servers']),
                ];
                $result['total_servers'] += count($serverResult['servers']);
            } else {
                $result['errors'][] = "Episode {$episode['title']}: " . ($serverResult['error'] ?? 'Unknown error');
            }
            
            // Delay between requests
            usleep(500000); // 500ms
        }
        
        $result['success'] = true;
        return $result;
    }
    
    /**
     * Cache HTML for later processing
     */
    protected function cacheHtml(string $url, string $html): string
    {
        $filename = md5($url) . '.html';
        $path = "{$this->storageDir}/{$filename}";
        Storage::put($path, $html);
        return $path;
    }
    
    /**
     * Get cached HTML
     */
    public function getCachedHtml(string $url): ?string
    {
        $filename = md5($url) . '.html';
        $path = "{$this->storageDir}/{$filename}";
        
        if (Storage::exists($path)) {
            return Storage::get($path);
        }
        
        return null;
    }
    
    /**
     * Check if URL is internal AnimeSail server
     */
    protected function isInternalServerUrl(string $url): bool
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
     * Guess server name from URL
     */
    protected function guessServerName(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        
        $serverNames = [
            'krakenfiles' => 'KrakenFiles',
            'filemoon' => 'Filemoon',
            'streamwish' => 'Streamwish',
            'mp4upload' => 'Mp4Upload',
            'doodstream' => 'Doodstream',
            'dood' => 'Doodstream',
            'mixdrop' => 'Mixdrop',
            'streamtape' => 'Streamtape',
            'vidmoly' => 'Vidmoly',
            'vidhide' => 'Vidhide',
            'voe' => 'Voe',
            'vtube' => 'Vtube',
            'yourupload' => 'YourUpload',
            'upstream' => 'Upstream',
            'streamhub' => 'Streamhub',
            'gdriveplayer' => 'GDrive',
            'sbembed' => 'Sbembed',
            'sbvideo' => 'Sbvideo',
            'streamsb' => 'StreamSB',
            'embedsito' => 'Embedsito',
            'supervideo' => 'SuperVideo',
            'videobin' => 'Videobin',
            'sendvid' => 'Sendvid',
            'vudeo' => 'Vudeo',
            'netu' => 'NetU',
            'hxfile' => 'HXFile',
        ];
        
        foreach ($serverNames as $pattern => $name) {
            if (stripos($host, $pattern) !== false) {
                return $name;
            }
        }
        
        // Extract domain name
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            return ucfirst($parts[count($parts) - 2]);
        }
        
        return 'Unknown';
    }
    
    /**
     * Get HTTP headers
     */
    protected function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9,id;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
        ];
    }
}
