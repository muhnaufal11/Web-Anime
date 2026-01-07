<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for Doodstream/Dood video embed
 * URL patterns:
 * - https://dood.wf/e/XXXXX
 * - https://doodstream.com/e/XXXXX  
 * - https://dood.so/e/XXXXX
 */
class DoodstreamExtractor
{
    /**
     * Check if URL is supported
     */
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/dood(?:stream)?\.(?:wf|com|so|to|la|pm|sh|ws|watch|re|cx|yt)\/[ed]\//i', $url);
    }
    
    /**
     * Extract direct video URL
     */
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'doodstream_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // First request to get the page
            $host = parse_url($embedUrl, PHP_URL_HOST);
            $baseUrl = parse_url($embedUrl, PHP_URL_SCHEME) . '://' . $host;
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => $baseUrl . '/',
            ])->timeout(15)->get($embedUrl);
            
            if (!$response->successful()) {
                Log::warning('Doodstream: Failed to fetch page', ['url' => $embedUrl, 'status' => $response->status()]);
                return null;
            }
            
            $html = $response->body();
            
            // Doodstream uses a pass_md5 endpoint to get the video URL
            // Pattern: /pass_md5/XXXXX/YYYYY
            if (preg_match('/\/pass_md5\/([^"\'<>\s]+)/i', $html, $match)) {
                $passMd5Path = '/pass_md5/' . $match[1];
                $passMd5Url = $baseUrl . $passMd5Path;
                
                // Make request to pass_md5
                $tokenResponse = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Referer' => $embedUrl,
                ])->timeout(15)->get($passMd5Url);
                
                if ($tokenResponse->successful()) {
                    $tokenData = $tokenResponse->body();
                    
                    // The response is the base video URL, we need to append token
                    if (filter_var(trim($tokenData), FILTER_VALIDATE_URL)) {
                        // Generate random string for token
                        $token = self::generateToken();
                        $expiry = time();
                        
                        $directUrl = trim($tokenData) . $token . '?token=' . $token . '&expiry=' . $expiry;
                        
                        $result = [
                            'url' => $directUrl,
                            'type' => 'video/mp4',
                            'host' => 'doodstream',
                            'headers' => [
                                'Referer' => $embedUrl,
                            ],
                        ];
                        
                        // Cache for only 1 hour (Dood links expire)
                        Cache::put($cacheKey, $result, 3600);
                        
                        return $result;
                    }
                }
            }
            
            Log::warning('Doodstream: Could not extract video URL', ['url' => $embedUrl]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Doodstream: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Generate random token for Doodstream
     */
    private static function generateToken(int $length = 10): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $result;
    }
}
