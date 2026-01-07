<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for KotakAnimeID video embed (used by NontonAnimeID)
 * URL pattern: https://s1.kotakanimeid.link/video-embed/?vid=BASE64_ENCODED_DATA
 */
class KotakAnimeIdExtractor
{
    /**
     * Check if URL is supported
     */
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/kotakanimeid\.link\/video-embed/i', $url);
    }
    
    /**
     * Extract direct video URL
     */
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'kotakanimeid_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Fetch the embed page
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://s7.nontonanimeid.boats/',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                Log::warning('KotakAnimeId: Failed to fetch page', ['url' => $embedUrl, 'status' => $response->status()]);
                return null;
            }
            
            $html = $response->body();
            
            // Method 1: Extract from video source/file in JavaScript
            $directUrl = self::extractFromScript($html);
            
            // Method 2: Try packed JavaScript
            if (!$directUrl) {
                $directUrl = self::extractFromPackedJs($html);
            }
            
            // Method 3: Look for HLS (m3u8) sources
            if (!$directUrl) {
                $directUrl = self::extractHlsSource($html);
            }
            
            // Method 4: Look for MP4 direct links
            if (!$directUrl) {
                $directUrl = self::extractMp4Source($html);
            }
            
            if ($directUrl) {
                $type = str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4';
                
                $result = [
                    'url' => $directUrl,
                    'type' => $type,
                    'host' => 'kotakanimeid',
                ];
                
                Cache::put($cacheKey, $result, 7200); // Cache 2 hours
                
                return $result;
            }
            
            Log::warning('KotakAnimeId: Could not extract video URL', ['url' => $embedUrl]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('KotakAnimeId: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Extract from inline JavaScript
     */
    private static function extractFromScript(string $html): ?string
    {
        // Pattern 1: file: "URL" or source: "URL"
        $patterns = [
            '/(?:file|source|src)\s*[:=]\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/',
            '/sources\s*:\s*\[\s*\{\s*(?:file|src)\s*:\s*["\']([^"\']+)["\']/',
            '/player\.src\s*\(\s*["\']([^"\']+)["\']/',
            '/videoUrl\s*=\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/',
            '/atob\s*\(["\']([A-Za-z0-9+\/=]+)["\']\)/', // Base64 encoded URL
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                $url = $match[1];
                
                // Check if it's base64 encoded
                if (preg_match('/^[A-Za-z0-9+\/=]{20,}$/', $url) && base64_decode($url, true)) {
                    $decoded = base64_decode($url);
                    if (filter_var($decoded, FILTER_VALIDATE_URL)) {
                        $url = $decoded;
                    }
                }
                
                // Validate URL
                if (filter_var($url, FILTER_VALIDATE_URL) && !str_ends_with($url, '.js')) {
                    return $url;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract from packed JavaScript
     */
    private static function extractFromPackedJs(string $html): ?string
    {
        if (preg_match('/eval\(function\(p,a,c,k,e,d\)\{.*?\}\(\'(.*?)\',(\d+),(\d+),\'(.*?)\'\.split/s', $html, $matches)) {
            $packed = $matches[1];
            $a = (int) $matches[2];
            $c = (int) $matches[3];
            $keywords = explode('|', $matches[4]);
            
            $unpacked = self::unpack($packed, $a, $c, $keywords);
            
            // Find video URL in unpacked code
            if (preg_match('/https?:\/\/[^"\'>\s]+\.(?:mp4|m3u8)[^"\'>\s]*/i', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[0]);
            }
        }
        
        return null;
    }
    
    /**
     * Extract HLS m3u8 source
     */
    private static function extractHlsSource(string $html): ?string
    {
        // Look for m3u8 URLs
        if (preg_match('/https?:\/\/[^"\'>\s]+\.m3u8[^"\'>\s]*/i', $html, $match)) {
            return html_entity_decode($match[0]);
        }
        
        return null;
    }
    
    /**
     * Extract MP4 direct source
     */
    private static function extractMp4Source(string $html): ?string
    {
        // Look for MP4 URLs (excluding JS files)
        if (preg_match('/https?:\/\/[^"\'>\s]+\.mp4[^"\'>\s]*/i', $html, $match)) {
            $url = html_entity_decode($match[0]);
            // Make sure it's not a player.mp4.js or similar
            if (!preg_match('/\.mp4\.(js|css)/i', $url)) {
                return $url;
            }
        }
        
        return null;
    }
    
    /**
     * Unpack p,a,c,k,e,d JavaScript
     */
    private static function unpack(string $packed, int $a, int $c, array $keywords): string
    {
        $baseConvert = function($num, $base) {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $result = '';
            while ($num > 0) {
                $result = $chars[$num % $base] . $result;
                $num = intdiv($num, $base);
            }
            return $result ?: '0';
        };
        
        $result = $packed;
        while ($c > 0) {
            $c--;
            if (!empty($keywords[$c])) {
                $encoded = $a > 62 ? $baseConvert($c, $a) : ($a > 36 ? $baseConvert($c, 36) : base_convert((string)$c, 10, $a));
                $result = preg_replace('/\b' . preg_quote($encoded, '/') . '\b/', $keywords[$c], $result);
            }
        }
        
        return $result;
    }
}
