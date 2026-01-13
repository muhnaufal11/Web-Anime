<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Mp4UploadExtractor
{
    /**
     * Check if URL is supported
     */
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/mp4upload\.com/i', $url);
    }
    
    /**
     * Extract direct video URL from MP4Upload embed
     * 
     * @param string $embedUrl The embed URL (e.g., https://www.mp4upload.com/embed-XXXX.html)
     * @return array|null ['url' => direct_url, 'type' => 'video/mp4'] or null on failure
     */
    public static function extract(string $embedUrl): ?array
    {
        // Cache key based on URL
        $cacheKey = 'mp4upload_' . md5($embedUrl);
        
        // Check cache first (cache for 2 hours)
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Normalize URL
            $embedUrl = self::normalizeUrl($embedUrl);
            
            if (!$embedUrl) {
                return null;
            }
            
            // Fetch the embed page
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://www.mp4upload.com/',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                Log::warning('Mp4Upload: Failed to fetch page', ['url' => $embedUrl, 'status' => $response->status()]);
                return null;
            }
            
            $html = $response->body();
            
            // Method 1: Try to find eval(function(p,a,c,k,e,d) packed JS
            $directUrl = self::extractFromPackedJs($html);
            
            // Method 2: Try to find direct src in player config
            if (!$directUrl) {
                $directUrl = self::extractFromPlayerConfig($html);
            }
            
            // Method 3: Try to find in script tags
            if (!$directUrl) {
                $directUrl = self::extractFromScriptTags($html);
            }
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => 'video/mp4',
                    'host' => 'mp4upload',
                ];
                
                // Cache for 30 minutes only (URLs expire quickly)
                Cache::put($cacheKey, $result, 1800);
                
                return $result;
            }
            
            Log::warning('Mp4Upload: Could not extract video URL', ['url' => $embedUrl]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Mp4Upload: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Normalize mp4upload URL to embed format
     */
    private static function normalizeUrl(string $url): ?string
    {
        // If already embed URL
        if (preg_match('/mp4upload\.com\/embed-([a-zA-Z0-9]+)/i', $url, $matches)) {
            return "https://www.mp4upload.com/embed-{$matches[1]}.html";
        }
        
        // If regular URL (mp4upload.com/XXXX)
        if (preg_match('/mp4upload\.com\/([a-zA-Z0-9]+)/i', $url, $matches)) {
            return "https://www.mp4upload.com/embed-{$matches[1]}.html";
        }
        
        return null;
    }
    
    /**
     * Extract URL from packed JavaScript (eval(function(p,a,c,k,e,d)))
     */
    private static function extractFromPackedJs(string $html): ?string
    {
        // Find packed JS
        if (preg_match('/eval\(function\(p,a,c,k,e,d\)\{.*?\}\(\'(.*?)\',(\d+),(\d+),\'(.*?)\'\.split/s', $html, $matches)) {
            $packed = $matches[1];
            $a = (int) $matches[2];
            $c = (int) $matches[3];
            $keywords = explode('|', $matches[4]);
            
            $unpacked = self::unpack($packed, $a, $c, $keywords);
            
            // Find video URL in unpacked code - look for actual video file URLs
            // Pattern for mp4upload video URLs with port number (like a2.mp4upload.com:183)
            if (preg_match('/https?:\/\/[a-z0-9]+\.mp4upload\.com(?::\d+)?\/d\/[^"\'>\s]+\.mp4/i', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[0]);
            }
            
            // Alternative pattern
            if (preg_match('/player\.src\(["\']?(https?:\/\/[^"\'>\s]+)/i', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[1]);
            }
        }
        
        return null;
    }
    
    /**
     * Unpack p,a,c,k,e,d JavaScript
     */
    private static function unpack(string $packed, int $a, int $c, array $keywords): string
    {
        // Create base conversion function
        $baseConvert = function($num, $base) {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $result = '';
            while ($num > 0) {
                $result = $chars[$num % $base] . $result;
                $num = intdiv($num, $base);
            }
            return $result ?: '0';
        };
        
        // Replace encoded values
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
    
    /**
     * Extract from player configuration
     */
    private static function extractFromPlayerConfig(string $html): ?string
    {
        // MP4Upload uses player.src() with the direct URL
        // Pattern: player.src({ type: "video/mp4", src: "https://a2.mp4upload.com:183/d/.../video.mp4" });
        if (preg_match('/player\.src\s*\(\s*\{[^}]*src\s*:\s*["\']([^"\']+\.mp4)["\']/', $html, $match)) {
            $url = $match[1];
            // Validate it's an actual video URL (should have mp4upload.com domain with /d/ path)
            if (preg_match('/https?:\/\/[a-z0-9]+\.mp4upload\.com(?::\d+)?\/d\//', $url)) {
                return html_entity_decode($url);
            }
        }
        
        // Alternative: Look for src in video source
        // Pattern for mp4upload video URLs with port number (like a2.mp4upload.com:183)
        if (preg_match('/https?:\/\/[a-z0-9]+\.mp4upload\.com(?::\d+)?\/d\/[^"\'>\s]+\.mp4/i', $html, $urlMatch)) {
            return html_entity_decode($urlMatch[0]);
        }
        
        // Generic pattern for other formats
        $patterns = [
            '/type\s*:\s*["\']video\/mp4["\']\s*,\s*src\s*:\s*["\']([^"\']+)["\']/',
            '/sources\s*:\s*\[\s*\{\s*(?:file|src)["\s:=]+["\']?(https?:\/\/[^"\'>\s]+)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $url = $matches[1];
                // Make sure it's not a .js file
                if (!preg_match('/\.(js|css)$/i', $url)) {
                    return html_entity_decode($url);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract from script tags
     */
    private static function extractFromScriptTags(string $html): ?string
    {
        // Find all script tags
        if (preg_match_all('/<script[^>]*>(.*?)<\/script>/si', $html, $scripts)) {
            foreach ($scripts[1] as $script) {
                // Look for mp4upload CDN video URLs (priority)
                // Pattern: https://a2.mp4upload.com:183/d/xxxxx/video.mp4
                if (preg_match('/https?:\/\/[a-z0-9]+\.mp4upload\.com(?::\d+)?\/d\/[^"\'>\s]+\.mp4/i', $script, $match)) {
                    return html_entity_decode($match[0]);
                }
            }
        }
        
        return null;
    }
}
