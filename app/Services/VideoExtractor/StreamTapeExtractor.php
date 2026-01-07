<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for StreamTape video embed
 * URL patterns:
 * - https://streamtape.com/e/XXXXX
 * - https://streamtape.to/e/XXXXX
 */
class StreamTapeExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/streamtape\.(?:com|to|net|xyz)\/[ev]\//i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'streamtape_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://streamtape.com/',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // StreamTape uses a special pattern with robotlink
            // Pattern: document.getElementById('robotlink').innerHTML = 'URL_PART1' + 'URL_PART2'
            $directUrl = self::extractStreamTapeUrl($html);
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => 'video/mp4',
                    'host' => 'streamtape',
                ];
                
                // StreamTape links expire quickly, cache for 30 minutes only
                Cache::put($cacheKey, $result, 1800);
                return $result;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('StreamTape: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function extractStreamTapeUrl(string $html): ?string
    {
        // Pattern 1: innerHTML assignment with concatenation
        // document.getElementById('robotlink').innerHTML = '/get_video?id=xxx&expires=xxx&ip=xxx&token=xxx'+'xxx';
        if (preg_match("/getElementById\(['\"](?:robotlink|norobotlink)['\"].*?innerHTML\s*=\s*['\"]([^'\"]+)['\"]\s*\+\s*['\"]([^'\"]+)['\"]/s", $html, $match)) {
            $url = 'https://streamtape.com' . $match[1] . $match[2];
            return $url;
        }
        
        // Pattern 2: Direct URL in script
        if (preg_match("/['\"]([^'\"]*\/get_video[^'\"]+)['\"]/", $html, $match)) {
            $url = $match[1];
            if (!str_starts_with($url, 'http')) {
                $url = 'https://streamtape.com' . $url;
            }
            return $url;
        }
        
        // Pattern 3: Token-based URL construction
        if (preg_match('/var\s+token\s*=\s*[\'"]([^\'"]+)[\'"]/', $html, $tokenMatch)) {
            $token = $tokenMatch[1];
            
            // Look for base URL
            if (preg_match('/[\'"]([^\'"]*(streamtape|tapecontent)[^\'"]*\/[^\'"]+)[\'"]/', $html, $baseMatch)) {
                return $baseMatch[1] . '&token=' . $token;
            }
        }
        
        return null;
    }
}
