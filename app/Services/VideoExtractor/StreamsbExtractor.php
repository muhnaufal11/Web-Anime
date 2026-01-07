<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for StreamSB video embed
 * URL patterns:
 * - https://streamsb.net/e/XXXXX
 * - https://sbembed.com/e/XXXXX
 * - https://watchsb.com/e/XXXXX
 * - https://playersb.com/e/XXXXX
 */
class StreamsbExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/(?:streamsb|sbembed|watchsb|playersb|sbplay|embedsb|cloudemb|tubesb|sbfull)\.(?:net|com|to|live)\/(?:e|embed|play)\/[a-zA-Z0-9]+/i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'streamsb_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Extract video ID
            if (!preg_match('/\/(?:e|embed|play)\/([a-zA-Z0-9]+)/i', $embedUrl, $idMatch)) {
                return null;
            }
            
            $videoId = $idMatch[1];
            $host = parse_url($embedUrl, PHP_URL_HOST);
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => "https://{$host}/",
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // Try API method first
            $directUrl = self::extractFromApi($host, $videoId, $html);
            
            if (!$directUrl) {
                // Fallback to HTML parsing
                $directUrl = self::extractFromHtml($html);
            }
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4',
                    'host' => 'streamsb',
                    'headers' => [
                        'Referer' => "https://{$host}/",
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ],
                ];
                
                Cache::put($cacheKey, $result, 3600); // 1 hour cache
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('StreamSB: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function extractFromApi(string $host, string $videoId, string $html): ?string
    {
        // Try to find sources API endpoint
        $sources = [
            "https://{$host}/sources50/{$videoId}",
            "https://{$host}/sources/{$videoId}",
            "https://{$host}/sources43/{$videoId}",
        ];
        
        foreach ($sources as $apiUrl) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Referer' => "https://{$host}/e/{$videoId}",
                    'watchsb' => 'sbstream',
                ])->timeout(10)->get($apiUrl);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['stream_data']['file'])) {
                        return $data['stream_data']['file'];
                    }
                    
                    if (isset($data['link'])) {
                        return $data['link'];
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return null;
    }
    
    private static function extractFromHtml(string $html): ?string
    {
        // sources pattern
        if (preg_match('/sources\s*:\s*\[\s*\{\s*file\s*:\s*["\']([^"\']+)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // m3u8 URL
        if (preg_match('/["\']([^"\']*\.m3u8[^"\']*)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        // MP4 URL
        if (preg_match('/["\']([^"\']+\.mp4[^"\']*)["\']/', $html, $match)) {
            return html_entity_decode($match[1]);
        }
        
        return null;
    }
}
