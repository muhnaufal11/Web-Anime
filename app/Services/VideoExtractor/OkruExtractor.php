<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for OK.ru (Odnoklassniki) video embed
 * URL patterns:
 * - https://ok.ru/videoembed/XXXXX
 * - https://ok.ru/video/XXXXX
 */
class OkruExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/ok\.ru\/(?:videoembed|video)\//i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'okru_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://ok.ru/',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // OK.ru stores video data in data-options JSON
            $directUrl = self::extractFromDataOptions($html);
            
            if (!$directUrl) {
                $directUrl = self::extractFromFlashvars($html);
            }
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => 'video/mp4',
                    'host' => 'okru',
                ];
                
                Cache::put($cacheKey, $result, 7200);
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Okru: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function extractFromDataOptions(string $html): ?string
    {
        // data-options contains JSON with video URLs
        if (preg_match('/data-options=["\']({.+?})["\']/', $html, $match)) {
            $json = html_entity_decode($match[1]);
            $data = json_decode($json, true);
            
            if (isset($data['flashvars']['metadata'])) {
                $metadata = json_decode($data['flashvars']['metadata'], true);
                
                // Get best quality video
                if (isset($metadata['videos'])) {
                    $videos = $metadata['videos'];
                    // Sort by quality (prefer higher)
                    usort($videos, fn($a, $b) => ($b['name'] ?? '') <=> ($a['name'] ?? ''));
                    
                    foreach ($videos as $video) {
                        if (!empty($video['url'])) {
                            return $video['url'];
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    private static function extractFromFlashvars(string $html): ?string
    {
        // Alternative: flashvars parameter
        if (preg_match('/flashvars["\s:=]+["\']{0,1}([^"\']+)/', $html, $match)) {
            parse_str($match[1], $params);
            
            if (!empty($params['metadata'])) {
                $metadata = json_decode(urldecode($params['metadata']), true);
                
                if (isset($metadata['videos'])) {
                    foreach ($metadata['videos'] as $video) {
                        if (!empty($video['url'])) {
                            return $video['url'];
                        }
                    }
                }
            }
        }
        
        // Direct video URL pattern
        if (preg_match('/https?:\/\/[^"\'>\s]*(?:vd|vid)[^"\'>\s]*\.mp4[^"\'>\s]*/i', $html, $match)) {
            return html_entity_decode($match[0]);
        }
        
        return null;
    }
}
