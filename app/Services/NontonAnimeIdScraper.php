<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class NontonAnimeIdScraper
{
    protected string $baseUrl = 'https://s7.nontonanimeid.boats';
    
    /**
     * Scrape episode page and get all video servers
     */
    public function scrapeEpisodePage(string $url): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Referer' => $this->baseUrl,
                ])
                ->get($url);
            
            if (!$response->successful()) {
                throw new \Exception("HTTP Error: " . $response->status());
            }
            
            return $this->parseEpisodeHtml($response->body(), $url);
            
        } catch (\Exception $e) {
            Log::error("NontonAnimeIdScraper Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'servers' => [],
            ];
        }
    }

    /**
     * Parse episode HTML to extract servers
     */
    public function parseEpisodeHtml(string $html, string $sourceUrl = ''): array
    {
        $data = [
            'success' => true,
            'title' => '',
            'episode_number' => null,
            'anime_title' => '',
            'servers' => [],
            'download_links' => [],
            'source_url' => $sourceUrl,
        ];

        // Extract title
        if (preg_match('/<h1[^>]*class="entry-title"[^>]*>([^<]+)/i', $html, $matches)) {
            $data['title'] = trim(html_entity_decode($matches[1]));
            
            // Parse anime title and episode number
            if (preg_match('/(.+?)\s+Episode\s+(\d+)/i', $data['title'], $parts)) {
                $data['anime_title'] = trim($parts[1]);
                $data['episode_number'] = (int) $parts[2];
            }
        }

        // Extract servers from player tabs
        // <li id="player-option-1" data-post="152760" data-type="Lokal-c" data-nume="1" class="...">
        if (preg_match_all('/<li[^>]*id="player-option-(\d+)"[^>]*data-post="(\d+)"[^>]*data-type="([^"]+)"[^>]*data-nume="(\d+)"[^>]*>.*?<span>([^<]+)<\/span>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['servers'][] = [
                    'option_id' => (int) $match[1],
                    'post_id' => $match[2],
                    'type' => $match[3],
                    'nume' => (int) $match[4],
                    'name' => trim(str_replace('S-', '', $match[5])),
                    'display_name' => trim($match[5]),
                ];
            }
        }

        // Extract main iframe URL (first server)
        if (preg_match('/<iframe[^>]*(?:data-src|src)="([^"]+)"[^>]*>/i', $html, $matches)) {
            $iframeUrl = $matches[1];
            if (!empty($data['servers'])) {
                $data['servers'][0]['embed_url'] = $iframeUrl;
            }
        }

        // Extract download links
        // <div class="listlink">...<a href="URL">ServerName</a>...
        if (preg_match('/<div class="listlink">(.*?)<\/div>/is', $html, $linkMatch)) {
            preg_match_all('/<a[^>]*href="([^"]+)"[^>]*>([^<]+)<\/a>/i', $linkMatch[1], $linkMatches, PREG_SET_ORDER);
            foreach ($linkMatches as $link) {
                $data['download_links'][] = [
                    'url' => $link[1],
                    'name' => trim($link[2]),
                ];
            }
        }

        // Extract AJAX data for server switching
        if (preg_match('/var\s+kotakajax\s*=\s*(\{[^}]+\})/is', $html, $ajaxMatch)) {
            // Try to decode the base64 script
            if (preg_match('/src="data:text\/javascript;base64,([^"]+)"/i', $html, $b64Match)) {
                $decoded = base64_decode($b64Match[1]);
                if (preg_match('/kotakajax\s*=\s*(\{[^\}]+\})/i', $decoded, $jsonMatch)) {
                    $data['ajax_config'] = json_decode($jsonMatch[1], true);
                }
            }
        }

        // Try to extract nonce for AJAX requests
        if (preg_match('/"nonce":"([^"]+)"/', $html, $nonceMatch)) {
            $data['nonce'] = $nonceMatch[1];
        }

        return $data;
    }

    /**
     * Fetch video embed URL for a specific server via AJAX
     */
    public function fetchServerEmbed(string $postId, string $serverType, int $nume, string $nonce): ?string
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Referer' => $this->baseUrl,
                ])
                ->post($this->baseUrl . '/wp-admin/admin-ajax.php', [
                    'action' => 'player_ajax',
                    'post' => $postId,
                    'type' => $serverType,
                    'nume' => $nume,
                    '_wpnonce' => $nonce,
                ]);

            if ($response->successful()) {
                $body = $response->body();
                // Extract iframe URL from response
                if (preg_match('/<iframe[^>]*src="([^"]+)"/i', $body, $matches)) {
                    return $matches[1];
                }
                // Maybe it's JSON response
                $json = json_decode($body, true);
                if (isset($json['embed_url'])) {
                    return $json['embed_url'];
                }
            }
        } catch (\Exception $e) {
            Log::error("NontonAnimeId AJAX Error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get all episode URLs from anime page
     */
    public function scrapeAnimePage(string $url): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Referer' => $this->baseUrl,
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception("HTTP Error: " . $response->status());
            }

            return $this->parseAnimeHtml($response->body(), $url);

        } catch (\Exception $e) {
            Log::error("NontonAnimeIdScraper Anime Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse anime page HTML
     */
    public function parseAnimeHtml(string $html, string $sourceUrl = ''): array
    {
        $data = [
            'success' => true,
            'title' => '',
            'episodes' => [],
            'source_url' => $sourceUrl,
        ];

        // Extract anime title
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
            $data['title'] = trim(html_entity_decode($matches[1]));
        }

        // Extract episode links
        // Pattern: <a href="URL">Episode X</a> or similar
        if (preg_match_all('/<a[^>]*href="([^"]*episode[^"]*)"[^>]*>.*?(?:Episode|Eps?)\.?\s*(\d+)/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $data['episodes'][] = [
                    'url' => $match[1],
                    'number' => (int) $match[2],
                ];
            }
        }

        // Sort by episode number
        usort($data['episodes'], fn($a, $b) => $a['number'] <=> $b['number']);

        return $data;
    }

    /**
     * Search anime on NontonAnimeID
     */
    public function searchAnime(string $query): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($this->baseUrl, [
                    's' => $query,
                ]);

            if (!$response->successful()) {
                return [];
            }

            $results = [];
            $html = $response->body();

            // Parse search results
            if (preg_match_all('/<article[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*src="([^"]+)"[^>]*alt="([^"]+)"/is', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $results[] = [
                        'url' => $match[1],
                        'poster' => $match[2],
                        'title' => html_entity_decode($match[3]),
                    ];
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error("NontonAnimeId Search Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Decode kotakanimeid video URL
     */
    public function decodeKotakUrl(string $encodedUrl): ?string
    {
        // The URL format: https://s1.kotakanimeid.link/video-embed/?vid=BASE64...
        if (preg_match('/vid=([A-Za-z0-9+\/=]+)/', $encodedUrl, $matches)) {
            $decoded = base64_decode($matches[1]);
            // The decoded content is usually encrypted, may need further decryption
            return $decoded;
        }
        return null;
    }

    /**
     * Match anime title with local database
     */
    public function findLocalAnime(string $title): ?\App\Models\Anime
    {
        // Clean the title
        $cleanTitle = preg_replace('/\s+(Season|S)\s*\d+/i', '', $title);
        $cleanTitle = preg_replace('/\s+Sub(title)?\s+Indo(nesia)?/i', '', $cleanTitle);
        $cleanTitle = trim($cleanTitle);

        // Try exact match first
        $anime = \App\Models\Anime::where('title', $title)->first();
        if ($anime) return $anime;

        // Try cleaned title
        $anime = \App\Models\Anime::where('title', $cleanTitle)->first();
        if ($anime) return $anime;

        // Try fuzzy match with LIKE
        $anime = \App\Models\Anime::where('title', 'LIKE', '%' . $cleanTitle . '%')->first();
        if ($anime) return $anime;

        // Try with slug
        $slug = Str::slug($cleanTitle);
        $anime = \App\Models\Anime::where('slug', 'LIKE', '%' . $slug . '%')->first();

        return $anime;
    }

    /**
     * Bulk sync servers from NontonAnimeID to local episode
     */
    public function syncServersToEpisode(\App\Models\Episode $episode, array $servers): array
    {
        $synced = [];
        $errors = [];

        foreach ($servers as $server) {
            try {
                // Check if server already exists
                $existingServer = $episode->videoServers()
                    ->where('server_name', $server['name'])
                    ->where('source', 'nontonanimeid')
                    ->first();

                if ($existingServer) {
                    // Update existing
                    $existingServer->update([
                        'embed_url' => $server['embed_url'] ?? $existingServer->embed_url,
                    ]);
                    $synced[] = $server['name'] . ' (updated)';
                } else {
                    // Create new
                    $episode->videoServers()->create([
                        'server_name' => $server['name'],
                        'embed_url' => $server['embed_url'] ?? '',
                        'source' => 'nontonanimeid',
                        'quality' => $this->guessQuality($server['name']),
                        'is_active' => true,
                    ]);
                    $synced[] = $server['name'] . ' (new)';
                }
            } catch (\Exception $e) {
                $errors[] = $server['name'] . ': ' . $e->getMessage();
            }
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
        ];
    }

    /**
     * Guess video quality from server name
     */
    protected function guessQuality(string $serverName): string
    {
        $name = strtolower($serverName);
        if (str_contains($name, '1080') || str_contains($name, 'uhd')) {
            return '1080p';
        }
        if (str_contains($name, '720') || str_contains($name, 'hd')) {
            return '720p';
        }
        if (str_contains($name, '480')) {
            return '480p';
        }
        if (str_contains($name, '360')) {
            return '360p';
        }
        return '720p'; // default
    }
}
