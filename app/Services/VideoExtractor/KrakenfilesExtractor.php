<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for Krakenfiles video embed
 * URL patterns:
 * - https://krakenfiles.com/embed-video/XXXXX
 * - https://krakenfiles.com/view/XXXXX
 * 
 * KrakenFiles uses a token-based system. The embed page contains
 * a download token that can be used to get the direct file URL.
 */
class KrakenfilesExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/krakenfiles\.com\/(?:embed-video|view)\//i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'krakenfiles_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Extract file hash
            if (!preg_match('/krakenfiles\.com\/(?:embed-video|view)\/([a-zA-Z0-9]+)/i', $embedUrl, $hashMatch)) {
                Log::warning('Krakenfiles: Could not extract file hash', ['url' => $embedUrl]);
                return null;
            }
            
            $fileHash = $hashMatch[1];
            Log::info('Krakenfiles: Extracting', ['hash' => $fileHash]);
            
            // Get embed page to find download token
            $embedResponse = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Referer' => 'https://krakenfiles.com/',
                ])
                ->timeout(20)
                ->get("https://krakenfiles.com/embed-video/{$fileHash}");
            
            if (!$embedResponse->successful()) {
                Log::warning('Krakenfiles: Embed page not accessible', ['status' => $embedResponse->status()]);
                return null;
            }
            
            $html = $embedResponse->body();
            
            // Try multiple extraction methods
            $directUrl = self::extractFromVideoTag($html)
                ?? self::extractFromScript($html)
                ?? self::extractFromApi($fileHash, $html);
            
            if ($directUrl) {
                $result = [
                    'url' => $directUrl,
                    'type' => str_contains($directUrl, '.m3u8') ? 'application/x-mpegURL' : 'video/mp4',
                    'host' => 'krakenfiles',
                    'headers' => [
                        'Referer' => 'https://krakenfiles.com/',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ],
                ];
                
                Log::info('Krakenfiles: Extraction successful', ['url' => $directUrl]);
                Cache::put($cacheKey, $result, 3600); // 1 hour cache (URLs may expire)
                return $result;
            }
            
            Log::warning('Krakenfiles: Could not extract video URL', ['hash' => $fileHash]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Krakenfiles: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function extractFromVideoTag(string $html): ?string
    {
        // Pattern: <source src="..." type="video/mp4">
        if (preg_match('/<source[^>]*src=["\']([^"\']+)["\'][^>]*type=["\']video\/mp4["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // Pattern: <source src="...mp4">
        if (preg_match('/<source[^>]*src=["\']([^"\']+\.mp4[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // Pattern: <video src="...">
        if (preg_match('/<video[^>]*src=["\']([^"\']+)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function extractFromScript(string $html): ?string
    {
        // Look for direct URL in JavaScript
        // Pattern: file: "https://..." or src: "https://..."
        if (preg_match('/(?:file|src|url)\s*[:=]\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // Pattern: URL in data attributes
        if (preg_match('/data-(?:src|url|file)\s*=\s*["\']([^"\']+\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        // Pattern: any CDN URL pattern
        if (preg_match('/["\']([^"\']*(?:cdn|media|stream)[^"\']*\.(?:mp4|m3u8)[^"\']*)["\']/', $html, $match)) {
            return self::cleanUrl($match[1]);
        }
        
        return null;
    }
    
    private static function extractFromApi(string $fileHash, string $html): ?string
    {
        // Try to find download token in HTML
        if (preg_match('/data-token=["\']([^"\']+)["\']/', $html, $tokenMatch)) {
            $token = $tokenMatch[1];
            
            // Use API to get download link
            try {
                $apiResponse = Http::withoutVerifying()
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Referer' => "https://krakenfiles.com/embed-video/{$fileHash}",
                        'X-Requested-With' => 'XMLHttpRequest',
                    ])
                    ->post("https://krakenfiles.com/lrepository/get", [
                        'hash' => $fileHash,
                        'token' => $token,
                    ]);
                
                if ($apiResponse->successful()) {
                    $data = $apiResponse->json();
                    if (isset($data['url'])) {
                        return $data['url'];
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Krakenfiles API error', ['error' => $e->getMessage()]);
            }
        }
        
        // Try direct API without token
        try {
            $apiResponse = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Referer' => "https://krakenfiles.com/embed-video/{$fileHash}",
                ])
                ->get("https://krakenfiles.com/getEmbed/{$fileHash}");
            
            if ($apiResponse->successful()) {
                $data = $apiResponse->json();
                if (isset($data['url'])) {
                    return $data['url'];
                }
                if (isset($data['file'])) {
                    return $data['file'];
                }
            }
        } catch (\Exception $e) {
            Log::debug('Krakenfiles getEmbed error', ['error' => $e->getMessage()]);
        }
        
        return null;
    }
    
    private static function cleanUrl(string $url): string
    {
        $url = html_entity_decode(trim($url));
        
        // Ensure URL is absolute
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        
        return $url;
    }
}
