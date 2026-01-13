<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for VTube video embed (commonly used in Indonesia)
 * URL patterns:
 * - https://vtube.to/embed-XXXXX.html
 * - https://vtbe.to/embed-XXXXX.html
 * - https://vtube.network/embed-XXXXX.html
 */
class VtubeExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/(?:vtube|vtbe)\.(?:to|network)\/(?:embed-)?[a-zA-Z0-9]+/i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'vtube_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://vtube.to/',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // Check for packed JS
            if (preg_match('/eval\(function\(p,a,c,k,e,d\).*?\)\)/', $html, $packedMatch)) {
                $unpacked = self::unpack($packedMatch[0]);
                $html .= "\n" . $unpacked;
            }
            
            // Try multiple extraction methods
            $directUrl = self::extractFromSources($html)
                ?? self::extractFromJwPlayer($html)
                ?? self::extractFromScript($html);
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4',
                    'host' => 'vtube',
                ];
                
                Cache::put($cacheKey, $result, 7200);
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Vtube: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function extractFromSources(string $html): ?string
    {
        // sources: [{file:"..."}]
        if (preg_match('/sources\s*:\s*\[\s*\{\s*file\s*:\s*["\']([^"\']+)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // sources: ["..."]
        if (preg_match('/sources\s*:\s*\[\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function extractFromJwPlayer(string $html): ?string
    {
        // jwplayer("player").setup({file:"..."})
        if (preg_match('/jwplayer[^}]*file\s*:\s*["\']([^"\']+)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function extractFromScript(string $html): ?string
    {
        // Generic file pattern
        if (preg_match('/["\']?file["\']?\s*[:=]\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // Direct URL pattern
        if (preg_match('/["\']([^"\']*(?:vtube|vtbe)[^"\']*\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function cleanUrl(string $url): string
    {
        return html_entity_decode(trim($url));
    }
    
    /**
     * Unpack packed JavaScript
     */
    private static function unpack(string $packed): string
    {
        if (!preg_match('/eval\(function\(p,a,c,k,e,d\)\{[^}]*\}return p\}\([\'"]([^\'"]*)[\'"],(\d+),(\d+),[\'"]([^\'"]*)[\'"]\.split\([\'"\|\'"]/', $packed, $match)) {
            return '';
        }
        
        $p = $match[1];
        $a = (int) $match[2];
        $c = (int) $match[3];
        $k = explode('|', $match[4]);
        
        while ($c--) {
            if (isset($k[$c]) && $k[$c] !== '') {
                $word = self::baseConvert($c, $a);
                $p = preg_replace('/\b' . preg_quote($word, '/') . '\b/', $k[$c], $p);
            }
        }
        
        return $p;
    }
    
    private static function baseConvert(int $num, int $base): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($num < $base) {
            return $chars[$num];
        }
        return self::baseConvert((int)($num / $base), $base) . $chars[$num % $base];
    }
}
