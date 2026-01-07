<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Extractor for GoFile video embed
 * URL patterns:
 * - https://gofile.io/d/XXXXX
 * - https://gofile.io/embed/XXXXX
 */
class GofileExtractor
{
    public static function isSupported(string $url): bool
    {
        return (bool) preg_match('/gofile\.io\/(d|embed)\//i', $url);
    }
    
    public static function extract(string $embedUrl): ?array
    {
        $cacheKey = 'gofile_' . md5($embedUrl);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        try {
            // Extract content ID
            if (!preg_match('/gofile\.io\/(?:d|embed)\/([a-zA-Z0-9]+)/i', $embedUrl, $idMatch)) {
                return null;
            }
            
            $contentId = $idMatch[1];
            
            // Get website token first
            $tokenResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(10)->get('https://gofile.io/dist/js/global.js');
            
            // Extract or generate token
            $token = self::getToken();
            
            // API request to get content
            $apiResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$token}",
            ])->timeout(15)->get("https://api.gofile.io/contents/{$contentId}?wt=4fd6sg89d7s6");
            
            if (!$apiResponse->successful()) {
                return null;
            }
            
            $data = $apiResponse->json();
            
            if (($data['status'] ?? '') !== 'ok') {
                return null;
            }
            
            // Extract video file
            $contents = $data['data']['children'] ?? [];
            
            foreach ($contents as $file) {
                if (isset($file['directLink']) && str_ends_with(strtolower($file['name'] ?? ''), '.mp4')) {
                    $result = [
                        'url' => $file['directLink'],
                        'type' => 'video/mp4',
                        'host' => 'gofile',
                    ];
                    
                    Cache::put($cacheKey, $result, 3600); // 1 hour cache
                    return $result;
                }
            }
            
            // Fallback: get first file with direct link
            foreach ($contents as $file) {
                if (isset($file['directLink'])) {
                    $result = [
                        'url' => $file['directLink'],
                        'type' => self::getContentType($file['name'] ?? ''),
                        'host' => 'gofile',
                    ];
                    
                    Cache::put($cacheKey, $result, 3600);
                    return $result;
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Gofile: Extraction error', ['url' => $embedUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    private static function getToken(): string
    {
        // Create guest account or use cached token
        $cacheKey = 'gofile_guest_token';
        
        if ($token = Cache::get($cacheKey)) {
            return $token;
        }
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->post('https://api.gofile.io/accounts');
            
            if ($response->successful() && ($response->json('status') === 'ok')) {
                $token = $response->json('data.token');
                Cache::put($cacheKey, $token, 86400); // 24 hours
                return $token;
            }
        } catch (\Exception $e) {
            Log::error('Gofile: Token error', ['error' => $e->getMessage()]);
        }
        
        return '';
    }
    
    private static function getContentType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return match ($ext) {
            'mp4', 'webm', 'mkv', 'avi' => 'video/mp4',
            'm3u8' => 'application/x-mpegURL',
            default => 'video/mp4',
        };
    }
}
