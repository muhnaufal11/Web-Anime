<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for RumbleVideo embed
 * URL patterns:
 * - https://rumble.com/embed/XXXXX/
 * - https://rumble.com/XXXXX.html
 */
class RumbleExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/rumble\.com\/(embed\/)?[a-zA-Z0-9]+/i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'rumble_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // Extract from embedUrl JSON
            $directUrl = self::extractFromJson($html)
                ?? self::extractFromScript($html);
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4',
                    'host' => 'rumble',
                ];
                
                Cache::put($cacheKey, $result, 7200);
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Rumble: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function extractFromJson(string $html): ?string
    {
        // Look for embedUrl JSON
        if (preg_match('/embedUrl["\']?\s*:\s*["\']([^"\']+)["\']/', $html, $match)) {
            $jsonUrl = html_entity_decode($match[1]);
            
            // Fetch JSON data
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])->timeout(10)->get($jsonUrl);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Get best quality
                    if (isset($data['u']['hls']['url'])) {
                        return $data['u']['hls']['url'];
                    }
                    
                    $qualities = ['1080', '720', '480', '360'];
                    foreach ($qualities as $q) {
                        if (isset($data['u']["mp4-{$q}"]['url'])) {
                            return $data['u']["mp4-{$q}"]['url'];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Continue to fallback
            }
        }
        
        return null;
    }
    
    private static function extractFromScript(string $html): ?string
    {
        // Direct MP4/M3U8 URL
        if (preg_match('/["\']([^"\']*rumble[^"\']*\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // Video source
        if (preg_match('/["\']?(?:mp4|hls)["\']?\s*:\s*\{\s*["\']?url["\']?\s*:\s*["\']([^"\']+)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        return null;
    }
}
