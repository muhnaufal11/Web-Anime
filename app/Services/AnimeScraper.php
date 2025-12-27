<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AnimeScraper
{
    /**
     * Parse anime detail from HTML content
     */
    public function parseAnimeFromHtml(string $html): array
    {
        $data = [
            'title' => '',
            'synopsis' => '',
            'type' => 'TV',
            'status' => 'Ongoing',
            'rating' => 0,
            'release_year' => null,
            'poster_url' => '',
            'genres' => [],
            'episodes' => [],
        ];

        // Extract title
        if (preg_match('/<h1[^>]*class="entry-title"[^>]*>([^<]+)/i', $html, $matches)) {
            $data['title'] = trim(str_replace('Subtitle Indonesia', '', html_entity_decode($matches[1])));
        }

        // Extract poster image
        if (preg_match('/<img[^>]*class="[^"]*wp-post-image[^"]*"[^>]*src="([^"]+)"/i', $html, $matches)) {
            $data['poster_url'] = $matches[1];
        }

        // Extract synopsis (paragraphs before the info table)
        if (preg_match_all('/<div class="entry-content[^"]*"[^>]*>.*?<p>(.+?)<\/p>/is', $html, $matches)) {
            $synopsis = '';
            foreach ($matches[1] as $p) {
                $text = strip_tags($p);
                if (strlen($text) > 50 && !str_contains($text, 'Tonton streaming')) {
                    $synopsis .= $text . "\n\n";
                }
            }
            $data['synopsis'] = trim($synopsis);
        }

        // Extract info from table
        if (preg_match('/<table>.*?<tbody>(.*?)<\/tbody>/is', $html, $tableMatch)) {
            $tableContent = $tableMatch[1];
            
            // Type
            if (preg_match('/<th>Tipe:<\/th>\s*<td>\s*([^<]+)/i', $tableContent, $m)) {
                $data['type'] = trim($m[1]);
            }
            
            // Status
            if (preg_match('/<th>Status:<\/th>\s*<td>\s*([^<]+)/i', $tableContent, $m)) {
                $data['status'] = trim($m[1]);
            }
            
            // Rating/Score
            if (preg_match('/<th>Skor Anime:<\/th>\s*<td>\s*([0-9.]+)/i', $tableContent, $m)) {
                $data['rating'] = (float) $m[1];
            }
            
            // Release Year
            if (preg_match('/<th>Dirilis:<\/th>\s*<td>\s*(\d{4})/i', $tableContent, $m)) {
                $data['release_year'] = (int) $m[1];
            }
            
            // Genres
            if (preg_match('/<th>Genre:<\/th>\s*<td>(.*?)<\/td>/is', $tableContent, $m)) {
                preg_match_all('/<a[^>]*>([^<]+)<\/a>/i', $m[1], $genreMatches);
                $data['genres'] = $genreMatches[1] ?? [];
            }
        }

        // Extract episodes list
        if (preg_match('/<ul class="daftar">(.*?)<\/ul>/is', $html, $listMatch)) {
            preg_match_all('/<li>\s*<a href="([^"]+)"[^>]*>([^<]+)<\/a>/i', $listMatch[1], $episodeMatches, PREG_SET_ORDER);
            
            foreach ($episodeMatches as $ep) {
                $epUrl = $ep[1];
                $epTitle = trim($ep[2]);
                
                // Extract episode number
                $epNumber = 1;
                if (preg_match('/episode[- ]?(\d+)/i', $epTitle, $numMatch)) {
                    $epNumber = (int) $numMatch[1];
                }
                
                $data['episodes'][] = [
                    'url' => $epUrl,
                    'title' => $epTitle,
                    'number' => $epNumber,
                ];
            }
            
            // Sort episodes by number (ascending)
            usort($data['episodes'], fn($a, $b) => $a['number'] <=> $b['number']);
        }

        return $data;
    }

    /**
     * Scrape episode page to get video servers
     */
    public function scrapeEpisodePage(string $url): array
    {
        $servers = [];
        
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);
            
            if (!$response->successful()) {
                return $servers;
            }
            
            $html = $response->body();
            $servers = $this->parseEpisodeServers($html);
            
        } catch (\Exception $e) {
            \Log::error("Error scraping episode: " . $e->getMessage());
        }
        
        return $servers;
    }

    /**
     * Parse episode HTML to extract video servers
     */
    public function parseEpisodeServers(string $html): array
    {
        $servers = [];
        
        // Pattern 1: AnimeSail mirror/server buttons
        // <a href="javascript:;" onclick="changemirror('URL')" ...>ServerName</a>
        if (preg_match_all('/onclick="changemirror\([\'"]([^\'"]+)[\'"]\)"[^>]*>([^<]+)/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $servers[] = [
                    'name' => trim(strip_tags($match[2])),
                    'url' => $this->decodeUrl($match[1]),
                ];
            }
        }
        
        // Pattern 2: Data attributes
        if (preg_match_all('/data-video="([^"]+)"[^>]*>([^<]*)</i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = trim(strip_tags($match[2])) ?: 'Server';
                $servers[] = [
                    'name' => $name,
                    'url' => $this->decodeUrl($match[1]),
                ];
            }
        }
        
        // Pattern 3: Iframe src
        if (preg_match_all('/<iframe[^>]*src="([^"]+)"[^>]*>/i', $html, $matches)) {
            foreach ($matches[1] as $i => $url) {
                if ($this->isVideoUrl($url)) {
                    $servers[] = [
                        'name' => 'Server ' . ($i + 1),
                        'url' => $this->decodeUrl($url),
                    ];
                }
            }
        }
        
        // Pattern 4: Select options
        if (preg_match_all('/<option[^>]*value="([^"]+)"[^>]*>([^<]+)/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if ($this->isVideoUrl($match[1])) {
                    $servers[] = [
                        'name' => trim($match[2]),
                        'url' => $this->decodeUrl($match[1]),
                    ];
                }
            }
        }
        
        // Remove duplicates
        $unique = [];
        foreach ($servers as $server) {
            $key = md5($server['url']);
            if (!isset($unique[$key])) {
                $unique[$key] = $server;
            }
        }
        
        return array_values($unique);
    }

    /**
     * Decode URL (base64 or other encoding)
     */
    protected function decodeUrl(string $url): string
    {
        // Try base64 decode
        if (preg_match('/^[a-zA-Z0-9+\/=]+$/', $url) && strlen($url) > 20) {
            $decoded = base64_decode($url, true);
            if ($decoded && filter_var($decoded, FILTER_VALIDATE_URL)) {
                return $decoded;
            }
        }
        
        // URL decode
        $url = urldecode($url);
        
        // Clean URL
        $url = html_entity_decode($url);
        
        return $url;
    }

    /**
     * Check if URL is likely a video URL
     */
    protected function isVideoUrl(string $url): bool
    {
        $videoHosts = [
            'mp4upload', 'streamtape', 'doodstream', 'filemoon', 'streamwish',
            'vidhide', 'embedgram', 'krakenfiles', 'gdrive', 'mega',
            'yourupload', 'archive.org', 'blogger.com', 'drive.google',
            'ok.ru', 'dailymotion', 'fembed', 'mixdrop', 'upstream',
        ];
        
        foreach ($videoHosts as $host) {
            if (stripos($url, $host) !== false) {
                return true;
            }
        }
        
        // Check for embed patterns
        if (preg_match('/embed|player|video|stream/i', $url)) {
            return true;
        }
        
        return false;
    }

    /**
     * Download and save poster image
     */
    public function downloadPoster(string $url, string $filename): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);
            
            if ($response->successful()) {
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $path = 'posters/' . $filename . '.' . $extension;
                
                \Storage::disk('public')->put($path, $response->body());
                
                return $path;
            }
        } catch (\Exception $e) {
            \Log::error("Error downloading poster: " . $e->getMessage());
        }
        
        return null;
    }
}
