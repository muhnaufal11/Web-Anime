<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for StreamWish/WishFast video embed
 * URL patterns:
 * - https://streamwish.to/e/XXXXX
 * - https://wishfast.top/e/XXXXX
 * - https://sfastwish.com/e/XXXXX
 */
class StreamWishExtractor
{
    /**
     * Check if URL is supported
     */
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/(?:streamwish|wishfast|sfastwish|strwish|awish|dwish)\.(?:to|com|top|net)\/e\//i', $url);
    }
    
    /**
     * Extract direct video URL
     */
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'streamwish_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => parse_url($embedUrl, PHP_URL_SCHEME) . '://' . parse_url($embedUrl, PHP_URL_HOST) . '/',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                Log::warning('StreamWish: Failed to fetch page', ['url' => $embedUrl, 'status' => $response->status()]);
                return null;
            }
            
            $html = $response->body();
            
            // Method 1: Extract from packed JavaScript
            $directUrl = self::extractFromPackedJs($html);
            
            // Method 2: Look for sources in script
            if (!$directUrl) {
                $directUrl = self::extractFromScript($html);
            }
            
            if ($directUrl) {
                $type = str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4';
                
                $result = [
                    'url' => $directUrl,
                    'type' => $type,
                    'host' => 'streamwish',
                ];
                
                Cache::put($cacheKey, $result, 7200);
                
                return $result;
            }
            
            Log::warning('StreamWish: Could not extract video URL', ['url' => $embedUrl]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('StreamWish: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Extract from packed JavaScript
     */
    private static function extractFromPackedJs(string $html): ?string
    {
        preg_match_all('/eval\(function\(p,a,c,k,e,d\)\{.*?\}\(\'(.*?)\',(\d+),(\d+),\'(.*?)\'\.split/s', $html, $allMatches, PREG_SET_ORDER);
        
        foreach ($allMatches as $matches) {
            $packed = $matches[1];
            $a = (int) $matches[2];
            $c = (int) $matches[3];
            $keywords = explode('|', $matches[4]);
            
            $unpacked = self::unpack($packed, $a, $c, $keywords);
            
            // Look for m3u8/mp4 URL
            if (preg_match('/file\s*:\s*["\']([^"\']+(?:\.m3u8|\.mp4)[^"\']*)["\']/', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[1]);
            }
            
            if (preg_match('/sources\s*:\s*\[\s*\{\s*file\s*:\s*["\']([^"\']+)["\']/', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[1]);
            }
        }
        
        return null;
    }
    
    /**
     * Extract from inline script
     */
    private static function extractFromScript(string $html): ?string
    {
        $patterns = [
            '/sources\s*:\s*\[\s*\{\s*file\s*:\s*["\']([^"\']+)["\']/',
            '/file\s*:\s*["\']([^"\']+\.m3u8[^"\']*)["\']/',
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
