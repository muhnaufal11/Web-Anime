<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for Vidoza video embed
 * URL patterns:
 * - https://vidoza.net/embed-XXXXX.html
 * - https://vidoza.net/XXXXX.html
 */
class VidozaExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/vidoza\.(?:net|org|co)\/(?:embed-)?[a-zA-Z0-9]+/i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'vidoza_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Normalize to embed URL
            $embedUrl = self::normalizeUrl($embedUrl);
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://vidoza.net/',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // Vidoza uses sourcesCode array
            $directUrl = self::extractFromSources($html);
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => 'video/mp4',
                    'host' => 'vidoza',
                ];
                
                Cache::put($cacheKey, $result, 7200);
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Vidoza: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function normalizeUrl(string $url): string
    {
        // Convert regular URL to embed URL
        if (preg_match('/vidoza\.(?:net|org|co)\/([a-zA-Z0-9]+)(?:\.html)?$/i', $url, $match)) {
            return "https://vidoza.net/embed-{$match[1]}.html";
        }
        return $url;
    }
    
    private static function extractFromSources(string $html): ?string
    {
        // Pattern: sourcesCode: [{src: "URL", ...}]
        if (preg_match('/sourcesCode\s*:\s*\[\s*\{\s*src\s*:\s*["\']([^"\']+)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // Alternative: sources array
        if (preg_match('/sources\s*:\s*\[\s*\{\s*(?:file|src)\s*:\s*["\']([^"\']+)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // Direct MP4 URL
        if (preg_match('/https?:\/\/[^"\'>\s]+\.mp4[^"\'>\s]*/i', $html, $match)) {
            $url = html_entity_decode($match[0]);
            // Exclude JS files
            if (!str_contains($url, '.js')) {
                return $url;
            }
        }
        
        return null;
    }
}
