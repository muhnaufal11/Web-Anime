<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for Mixdrop video embed
 * URL patterns:
 * - https://mixdrop.ag/e/XXXXX
 * - https://mixdrop.co/e/XXXXX
 * - https://mixdrop.to/e/XXXXX
 */
class MixdropExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/mixdrop\.(?:ag|co|to|sx|ch|bz|gl)\/e\//i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'mixdrop_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => parse_url($embedUrl, PHP_URL_SCHEME) . '://' . parse_url($embedUrl, PHP_URL_HOST) . '/',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // Method 1: Extract from packed JS
            $directUrl = self::extractFromPackedJs($html);
            
            // Method 2: Look for MDCore pattern
            if (!$directUrl) {
                $directUrl = self::extractFromMDCore($html);
            }
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => 'video/mp4',
                    'host' => 'mixdrop',
                ];
                
                // Cache for 30 minutes (URLs may expire)
                Cache::put($cacheKey, $result, 1800);
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Mixdrop: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function extractFromPackedJs(string $html): ?string
    {
        preg_match_all('/eval\(function\(p,a,c,k,e,d\)\{.*?\}\(\'(.*?)\',(\d+),(\d+),\'(.*?)\'\.split/s', $html, $allMatches, PREG_SET_ORDER);
        
        foreach ($allMatches as $matches) {
            $packed = $matches[1];
            $a = (int) $matches[2];
            $c = (int) $matches[3];
            $keywords = explode('|', $matches[4]);
            
            $unpacked = self::unpack($packed, $a, $c, $keywords);
            
            // MDCore.wurl pattern
            if (preg_match('/MDCore\.wurl\s*=\s*["\']([^"\']+)["\']/', $unpacked, $urlMatch)) {
                $url = $urlMatch[1];
                // Mixdrop uses // prefix
                if (str_starts_with($url, '//')) {
                    $url = 'https:' . $url;
                }
                return $url;
            }
            
            // Alternative: direct video URL
            if (preg_match('/https?:\/\/[^"\'>\s]+\.(?:mp4|m3u8)[^"\'>\s]*/i', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[0]);
            }
        }
        
        return null;
    }
    
    private static function extractFromMDCore(string $html): ?string
    {
        if (preg_match('/MDCore\.wurl\s*=\s*["\']([^"\']+)["\']/', $html, $match)) {
            $url = $match[1];
            if (str_starts_with($url, '//')) {
                $url = 'https:' . $url;
            }
            return $url;
        }
        return null;
    }
    
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
