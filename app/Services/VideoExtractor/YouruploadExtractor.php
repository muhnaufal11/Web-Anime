<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for YourUpload video embed
 * URL patterns:
 * - https://www.yourupload.com/embed/XXXXX
 * - https://yourupload.com/watch/XXXXX
 */
class YouruploadExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/yourupload\.com\/(?:embed|watch)\/[a-zA-Z0-9]+/i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'yourupload_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Normalize to embed URL
            $embedUrl = self::normalizeUrl($embedUrl);
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://www.yourupload.com/',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // Try multiple extraction methods
            $directUrl = self::extractFromVideo($html)
                ?? self::extractFromScript($html);
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4',
                    'host' => 'yourupload',
                ];
                
                Cache::put($cacheKey, $result, 7200);
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('YourUpload: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function normalizeUrl(string $url): string
    {
        // Convert watch URL to embed
        if (preg_match('/yourupload\.com\/watch\/([a-zA-Z0-9]+)/i', $url, $match)) {
            return "https://www.yourupload.com/embed/{$match[1]}";
        }
        return $url;
    }
    
    private static function extractFromVideo(string $html): ?string
    {
        // source tag
        if (preg_match('/<source[^>]*src=["\']([^"\']+)["\'][^>]*type=["\']video\/mp4["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // video src
        if (preg_match('/<video[^>]*src=["\']([^"\']+\.mp4[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function extractFromScript(string $html): ?string
    {
        // file: "..."
        if (preg_match('/file\s*:\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // sources: [{file:"..."}]
        if (preg_match('/sources\s*:\s*\[\s*\{\s*(?:file|src)\s*:\s*["\']([^"\']+)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // CDN URL
        if (preg_match('/["\']([^"\']*yourupload[^"\']*\.mp4[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // videoUrl or streamUrl
        if (preg_match('/(?:videoUrl|streamUrl|jwUrl)\s*[:=]\s*["\']([^"\']+)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function cleanUrl(string $url): string
    {
        return html_entity_decode(trim($url));
    }
}
