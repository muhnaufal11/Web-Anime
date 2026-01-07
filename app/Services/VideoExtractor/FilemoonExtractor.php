<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for Filemoon/Moonplayer video embed
 * URL patterns: 
 * - https://filemoon.sx/e/XXXXX
 * - https://moonplayer.cc/e/XXXXX
 */
class FilemoonExtractor
{
    /**
     * Check if URL is supported
     */
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/(?:filemoon|moonplayer|kerapoxy)\.(?:sx|cc|to|in|top|wf)\/e\//i', $url);
    }
    
    /**
     * Extract direct video URL
     */
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'filemoon_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => parse_url($embedUrl, PHP_URL_SCHEME) . '://' . parse_url($embedUrl, PHP_URL_HOST) . '/',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                Log::warning('Filemoon: Failed to fetch page', ['url' => $embedUrl, 'status' => $response->status()]);
                return null;
            }
            
            $html = $response->body();
            
            // Method 1: Extract from packed JavaScript
            $directUrl = self::extractFromPackedJs($html);
            
            // Method 2: Look for HLS sources
            if (!$directUrl) {
                $directUrl = self::extractHlsSource($html);
            }
            
            if ($directUrl) {
                $type = str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4';
                
                $result = [
                    'url' => $directUrl,
                    'type' => $type,
                    'host' => 'filemoon',
                ];
                
                Cache::put($cacheKey, $result, 7200);
                
                return $result;
            }
            
            Log::warning('Filemoon: Could not extract video URL', ['url' => $embedUrl]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Filemoon: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Extract from packed JavaScript (eval(function(p,a,c,k,e,d)))
     */
    private static function extractFromPackedJs(string $html): ?string
    {
        // Find all packed scripts
        preg_match_all('/eval\(function\(p,a,c,k,e,d\)\{.*?\}\(\'(.*?)\',(\d+),(\d+),\'(.*?)\'\.split/s', $html, $allMatches, PREG_SET_ORDER);
        
        foreach ($allMatches as $matches) {
            $packed = $matches[1];
            $a = (int) $matches[2];
            $c = (int) $matches[3];
            $keywords = explode('|', $matches[4]);
            
            $unpacked = self::unpack($packed, $a, $c, $keywords);
            
            // Look for m3u8 URL in unpacked code
            if (preg_match('/file\s*:\s*["\']([^"\']+\.m3u8[^"\']*)["\']/', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[1]);
            }
            
            // Generic HLS/MP4 URL
            if (preg_match('/https?:\/\/[^"\'>\s]+\.(?:m3u8|mp4)[^"\'>\s]*/i', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[0]);
            }
        }
        
        return null;
    }
    
    /**
     * Extract HLS source from HTML
     */
    private static function extractHlsSource(string $html): ?string
    {
        // Pattern for file/source URL
        $patterns = [
            '/file\s*:\s*["\']([^"\']+\.m3u8[^"\']*)["\']/',
            '/source\s*:\s*["\']([^"\']+\.m3u8[^"\']*)["\']/',
            '/src\s*:\s*["\']([^"\']+\.m3u8[^"\']*)["\']/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                return html_entity_decode($match[1]);
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
