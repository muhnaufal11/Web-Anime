<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for Dailymotion video embed
 * URL patterns:
 * - https://www.dailymotion.com/embed/video/XXXXX
 * - https://www.dailymotion.com/video/XXXXX
 * - https://dai.ly/XXXXX
 */
class DailymotionExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/(?:dailymotion\.com\/(?:embed\/)?video|dai\.ly)\/[a-zA-Z0-9]+/i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'dailymotion_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Extract video ID
            $videoId = self::extractVideoId($embedUrl);
            
            if (!$videoId) {
                return null;
            }
            
            // Use Dailymotion API
            $apiUrl = "https://www.dailymotion.com/player/metadata/video/{$videoId}";
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'application/json',
            ])->timeout(15)->get($apiUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $data = $response->json();
            
            // Get best quality URL
            $directUrl = self::extractBestQuality($data);
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4',
                    'host' => 'dailymotion',
                ];
                
                Cache::put($cacheKey, $result, 7200);
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Dailymotion: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function extractVideoId(string $url): ?string
    {
        // dai.ly shortlink
        if (preg_match('/dai\.ly\/([a-zA-Z0-9]+)/i', $url, $match)) {
            return $match[1];
        }
        
        // Full URL
        if (preg_match('/dailymotion\.com\/(?:embed\/)?video\/([a-zA-Z0-9]+)/i', $url, $match)) {
            return $match[1];
        }
        
        return null;
    }
    
    private static function extractBestQuality(array $data): ?string
    {
        // Try to get HLS stream
        if (isset($data['qualities']['auto'][0]['url'])) {
            return $data['qualities']['auto'][0]['url'];
        }
        
        // Try specific qualities
        $qualities = ['1080', '720', '480', '380', '240'];
        
        foreach ($qualities as $quality) {
            if (isset($data['qualities'][$quality][0]['url'])) {
                return $data['qualities'][$quality][0]['url'];
            }
        }
        
        // Fallback to any available URL
        if (isset($data['qualities']) && is_array($data['qualities'])) {
            foreach ($data['qualities'] as $quality) {
                if (is_array($quality) && isset($quality[0]['url'])) {
                    return $quality[0]['url'];
                }
            }
        }
        
        return null;
    }
}
