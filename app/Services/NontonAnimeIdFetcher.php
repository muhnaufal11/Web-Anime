<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NontonAnimeIdFetcher
{
    protected string $baseUrl = 'https://s7.nontonanimeid.boats';
    protected string $ajaxUrl = 'https://s7.nontonanimeid.boats/wp-admin/admin-ajax.php';
    
    /**
     * Fetch all video servers from a NontonAnimeID episode URL
     */
    public function fetchAllServers(string $episodeUrl): array
    {
        try {
            // Step 1: Fetch the episode page
            $response = Http::timeout(30)
                ->withHeaders($this->getHeaders())
                ->get($episodeUrl);
            
            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Failed to fetch page: ' . $response->status()];
            }
            
            $html = $response->body();
            
            // Step 2: Extract necessary data from page
            $pageData = $this->extractPageData($html);
            
            if (empty($pageData['servers'])) {
                return ['success' => false, 'error' => 'No servers found on page'];
            }
            
            // Step 3: Fetch embed URL for each server via AJAX
            $servers = [];
            foreach ($pageData['servers'] as $index => $server) {
                $embedUrl = null;
                
                // First server already has URL from iframe
                if ($index === 0 && !empty($server['url'])) {
                    $embedUrl = $server['url'];
                } else {
                    // Fetch via AJAX
                    $embedUrl = $this->fetchServerEmbed(
                        $pageData['post_id'],
                        $server['type'],
                        $server['nume'],
                        $pageData['nonce'],
                        $episodeUrl
                    );
                }
                
                if ($embedUrl) {
                    $servers[] = [
                        'name' => $server['name'],
                        'url' => $embedUrl,
                        'type' => $server['type'],
                        '_available_servers' => count($pageData['servers']), // Total server yang tersedia di source
                    ];
                }
                
                // Small delay to avoid rate limiting
                if ($index > 0) {
                    usleep(300000); // 300ms delay
                }
            }
            
            return [
                'success' => true,
                'title' => $pageData['title'] ?? '',
                'episode_number' => $pageData['episode_number'] ?? null,
                'servers' => $servers,
                'total_found' => count($pageData['servers']),
                'total_fetched' => count($servers),
            ];
            
        } catch (\Exception $e) {
            Log::error("NontonAnimeIdFetcher Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Extract necessary data from episode page HTML
     */
    protected function extractPageData(string $html): array
    {
        $data = [
            'post_id' => null,
            'nonce' => null,
            'servers' => [],
            'title' => '',
            'episode_number' => null,
        ];
        
        // Extract title
        if (preg_match('/<h1[^>]*class="entry-title"[^>]*>([^<]+)/i', $html, $m)) {
            $data['title'] = trim(html_entity_decode($m[1]));
            if (preg_match('/Episode\s*(\d+)/i', $data['title'], $epMatch)) {
                $data['episode_number'] = (int) $epMatch[1];
            }
        }
        
        // Extract nonce from inline script first
        if (preg_match('/var\s+kotakajax\s*=\s*\{[^}]*"nonce"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $data['nonce'] = $m[1];
        }
        
        // Try from ALL base64 scripts (search for kotakajax with nonce)
        if (empty($data['nonce'])) {
            preg_match_all('/src="data:text\/javascript;base64,([^"]+)"/i', $html, $b64Matches);
            foreach ($b64Matches[1] as $b64) {
                $decoded = base64_decode($b64);
                // Look for kotakajax with nonce
                if (preg_match('/kotakajax\s*=\s*\{[^}]*"nonce"\s*:\s*"([^"]+)"/', $decoded, $nonceMatch)) {
                    $data['nonce'] = $nonceMatch[1];
                    break;
                }
                // Also try generic nonce pattern
                if (empty($data['nonce']) && preg_match('/"nonce"\s*:\s*"([a-f0-9]+)"/', $decoded, $nonceMatch)) {
                    $data['nonce'] = $nonceMatch[1];
                }
            }
        }
        
        // Extract servers from player tabs
        preg_match_all('/<li[^>]*id="player-option-(\d+)"[^>]*data-post="(\d+)"[^>]*data-type="([^"]+)"[^>]*data-nume="(\d+)"[^>]*>.*?<span>([^<]+)<\/span>/is', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            if (empty($data['post_id'])) {
                $data['post_id'] = $match[2];
            }
            
            $data['servers'][] = [
                'option_id' => (int) $match[1],
                'type' => $match[3],
                'nume' => (int) $match[4],
                'name' => trim(preg_replace('/^S-/i', '', $match[5])),
                'url' => '', // Will be filled later
            ];
        }
        
        // Extract first server's iframe URL
        if (preg_match('/<iframe[^>]*(?:data-src|src)="([^"]+)"[^>]*>/i', $html, $iframeMatch)) {
            if (!empty($data['servers'])) {
                $data['servers'][0]['url'] = $iframeMatch[1];
            }
        }
        
        return $data;
    }
    
    /**
     * Fetch embed URL for a specific server via AJAX
     */
    protected function fetchServerEmbed(string $postId, string $serverType, int $nume, ?string $nonce, string $referer): ?string
    {
        try {
            $formData = [
                'action' => 'doo_player_ajax',
                'post' => $postId,
                'nume' => $nume,
                'type' => $serverType,
            ];
            
            // Add nonce if available
            if (!empty($nonce)) {
                $formData['nonce'] = $nonce;
            }
            
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
                ->post($this->ajaxUrl, $formData);
            
            if (!$response->successful()) {
                Log::warning("AJAX request failed for server {$serverType}: " . $response->status());
                return null;
            }
            
            $body = $response->body();
            
            // Debug log
            Log::debug("AJAX response for {$serverType}: status=" . $response->status() . ", body=" . substr($body, 0, 200));
            
            // Check for "0" response (WordPress AJAX rejection)
            if ($body === '0' || $body === '-1') {
                Log::warning("AJAX rejected for server {$serverType} (response: {$body})");
                return null;
            }
            
            // Response is usually JSON with embed_url or iframe HTML
            $json = json_decode($body, true);
            
            if (isset($json['embed_url'])) {
                return $json['embed_url'];
            }
            
            if (isset($json['type']) && $json['type'] === 'iframe' && isset($json['embed_url'])) {
                return $json['embed_url'];
            }
            
            // Try to extract iframe from HTML response
            if (preg_match('/<iframe[^>]*src="([^"]+)"/i', $body, $m)) {
                return $m[1];
            }
            
            // Try to extract from embed_url in response
            if (preg_match('/"embed_url"\s*:\s*"([^"]+)"/', $body, $m)) {
                return stripcslashes($m[1]);
            }
            
            Log::warning("Could not extract embed URL for server {$serverType}");
            return null;
            
        } catch (\Exception $e) {
            Log::error("AJAX fetch error for {$serverType}: " . $e->getMessage());
            return null;
        }
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
    
    /**
     * Sync all servers from NontonAnimeID URL to local episode
     * 
     * @param int $episodeId Episode ID
     * @param string $nontonAnimeIdUrl URL from NontonAnimeID
     * @param bool $deleteExisting Whether to delete existing servers first
     */
    public function syncToEpisode(int $episodeId, string $nontonAnimeIdUrl, bool $deleteExisting = false): array
    {
        $result = $this->fetchAllServers($nontonAnimeIdUrl);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Delete existing servers if requested
        if ($deleteExisting) {
            \App\Models\VideoServer::where('episode_id', $episodeId)->delete();
        }
        
        $created = 0;
        $updated = 0;
        $errors = [];
        
        foreach ($result['servers'] as $server) {
            try {
                // Filter invalid URLs
                if (empty($server['url']) || !preg_match('/^https?:\/\//i', $server['url'])) {
                    continue;
                }
                
                // Skip redirect links
                if (str_contains($server['url'], '/redirect/')) {
                    continue;
                }
                
                // Skip internal AnimeSail/NontonAnimeID servers (IP-based or internal proxies)
                // These servers require session cookies and won't work externally
                $skipPatterns = [
                    '154.26.137.28',      // AnimeSail IP
                    '185.217.95.',        // Other AnimeSail IPs
                    'nontonanimeid',      // Internal proxy
                    'animesail',          // Internal proxy
                    '/proxy/',            // Proxy endpoints
                    '/embed-local/',      // Local embeds
                ];
                
                $shouldSkip = false;
                foreach ($skipPatterns as $pattern) {
                    if (stripos($server['url'], $pattern) !== false) {
                        $shouldSkip = true;
                        Log::debug("Skipping internal server: {$server['name']} - {$server['url']}");
                        break;
                    }
                }
                
                if ($shouldSkip) {
                    continue;
                }
                
                $embedCode = \App\Services\VideoEmbedHelper::toEmbedCode($server['url'], $server['name']);
                
                $vs = \App\Models\VideoServer::updateOrCreate(
                    [
                        'episode_id' => $episodeId,
                        'embed_url' => $server['url'],
                    ],
                    [
                        'server_name' => $server['name'],
                        'embed_url' => $embedCode ?: $server['url'],
                        'is_active' => true,
                        'source' => 'nontonanimeid',
                    ]
                );
                
                if ($vs->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "{$server['name']}: {$e->getMessage()}";
            }
        }
        
        return [
            'success' => true,
            'created' => $created,
            'updated' => $updated,
            'total' => count($result['servers']),
            'errors' => $errors,
        ];
    }
}
