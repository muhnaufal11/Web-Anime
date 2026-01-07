<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for Acefile video embed
 * URL patterns:
 * - https://acefile.co/player/XXXXX
 * - https://acefile.co/embed/XXXXX
 * - https://acefile.co/f/XXXXX
 */
class AcefileExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/acefile\.co\/(?:player|embed|f)\//i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'acefile_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Convert f/ URLs to player/ format
            $embedUrl = preg_replace('/acefile\.co\/f\//', 'acefile.co/player/', $embedUrl);
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://acefile.co/',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                Log::warning('Acefile: Failed to fetch page', ['url' => $embedUrl, 'status' => $response->status()]);
                return null;
            }
            
            $html = $response->body();
            
            // Acefile stores video URL in player config
            $directUrl = self::extractFromConfig($html);
            
            // Try packed JS if config extraction failed
            if (!$directUrl) {
                $directUrl = self::extractFromPackedJs($html);
            }
            
            // Try video tag
            if (!$directUrl) {
                $directUrl = self::extractFromVideoTag($html);
            }
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4',
                    'host' => 'acefile',
                ];
                
                // Cache for 20 minutes (URLs expire)
                Cache::put($cacheKey, $result, 1200);
                return $result;
            }
            
            Log::warning('Acefile: Could not extract video URL', ['url' => $embedUrl]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Acefile: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function extractFromConfig(string $html): ?string
    {
        // Pattern: sources: [{file: "URL"}]
        if (preg_match('/sources\s*:\s*\[\s*\{\s*file\s*:\s*["\']([^"\']+)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // Pattern: file: "URL"
        if (preg_match('/file\s*:\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // Pattern: src: "URL"
        if (preg_match('/src\s*:\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // Pattern: source: "URL"
        if (preg_match('/source\s*:\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // Pattern: data-file="URL"
        if (preg_match('/data-file=["\']([^"\']+)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        return null;
    }
    
    private static function extractFromPackedJs(string $html): ?string
    {
        // Find packed JS
        if (preg_match('/eval\(function\(p,a,c,k,e,d\)\{.*?\}\(\'(.*?)\',(\d+),(\d+),\'(.*?)\'\.split/s', $html, $matches)) {
            $packed = $matches[1];
            $a = (int) $matches[2];
            $c = (int) $matches[3];
            $keywords = explode('|', $matches[4]);
            
            $unpacked = self::unpack($packed, $a, $c, $keywords);
            
            // Look for video URLs in unpacked code
            if (preg_match('/https?:\/\/[^"\'>\s]+\.(?:mp4|m3u8)[^"\'>\s]*/i', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[0]);
            }
            
            // Pattern: file:"URL"
            if (preg_match('/file\s*:\s*["\']([^"\']+)["\']/', $unpacked, $urlMatch)) {
                return html_entity_decode($urlMatch[1]);
            }
        }
        
        return null;
    }
    
    private static function extractFromVideoTag(string $html): ?string
    {
        // Direct video/source tag
        if (preg_match('/<(?:video|source)[^>]*src=["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // Video with source child
        if (preg_match('/<video[^>]*>.*?<source[^>]*src=["\']([^"\']+)["\'].*?<\/video>/si', $html, $match)) {
            return html_entity_decode($match[1]);
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
