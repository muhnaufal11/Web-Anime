<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for SendVid video embed
 * URL patterns:
 * - https://sendvid.com/embed/XXXXX
 * - https://sendvid.com/XXXXX
 */
class SendvidExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/sendvid\.com\/(embed\/)?[a-zA-Z0-9]+/i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'sendvid_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Normalize to embed URL
            $embedUrl = self::normalizeUrl($embedUrl);
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
                'Referer' => 'https://sendvid.com/',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // Try multiple extraction methods
            $directUrl = self::extractFromSource($html)
                ?? self::extractFromScript($html)
                ?? self::extractFromMeta($html);
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4',
                    'host' => 'sendvid',
                ];
                
                Cache::put($cacheKey, $result, 7200);
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Sendvid: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function normalizeUrl(string $url): string
    {
        // Convert direct URL to embed
        if (preg_match('/sendvid\.com\/([a-zA-Z0-9]+)$/i', $url, $match)) {
            return "https://sendvid.com/embed/{$match[1]}";
        }
        return $url;
    }
    
    private static function extractFromSource(string $html): ?string
    {
        // Pattern: source src="..."
        if (preg_match('/<source[^>]*src=["\']([^"\']+)["\'][^>]*type=["\']video\/mp4["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // Alternative source
        if (preg_match('/<source[^>]*src=["\']([^"\']+\.mp4[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function extractFromScript(string $html): ?string
    {
        // Pattern: var video_source = "..."
        if (preg_match('/(?:video_source|source|videoUrl)\s*[:=]\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // HLS pattern
        if (preg_match('/["\']([^"\']*\.m3u8[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // CDN URL pattern
        if (preg_match('/["\']([^"\']*sendvid[^"\']*\.mp4[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function extractFromMeta(string $html): ?string
    {
        // og:video or twitter:player:stream
        if (preg_match('/<meta[^>]*(?:og:video|twitter:player:stream)["\'][^>]*content=["\']([^"\']+)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function cleanUrl(string $url): string
    {
        return html_entity_decode(trim($url));
    }
}
