<?php

namespace App\Services\VideoExtractor;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VideoExtractorService
{
    /**
     * Supported extractors
     * Note: Some extractors disabled due to issues:
     * - krakenfiles: URLs expire quickly and embed often 404
     */
    protected static array $extractors = [
        'mp4upload' => Mp4UploadExtractor::class,
        'kotakanimeid' => KotakAnimeIdExtractor::class,
        'filemoon' => FilemoonExtractor::class,
        'streamwish' => StreamWishExtractor::class,
        'doodstream' => DoodstreamExtractor::class,
        'mixdrop' => MixdropExtractor::class,
        'streamtape' => StreamTapeExtractor::class,
        'okru' => OkruExtractor::class,
        'vidoza' => VidozaExtractor::class,
        'uqload' => UqloadExtractor::class,
        'acefile' => AcefileExtractor::class,
        // 'krakenfiles' => KrakenfilesExtractor::class, // Disabled - URLs expire quickly
        'gofile' => GofileExtractor::class,
        'sendvid' => SendvidExtractor::class,
        'vtube' => VtubeExtractor::class,
        'streamsb' => StreamsbExtractor::class,
        'yourupload' => YouruploadExtractor::class,
        'dailymotion' => DailymotionExtractor::class,
        'rumble' => RumbleExtractor::class,
    ];
    
    /**
     * Try to extract direct video URL from embed URL
     * 
     * @param string $embedUrl The embed URL
     * @return array|null ['url' => direct_url, 'type' => mime_type, 'host' => host_name] or null
     */
    public static function extract(string $embedUrl): ?array
    {
        // Check cache first for recently extracted URLs
        $cacheKey = 'video_extract_result_' . md5($embedUrl);
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            // Verify URL is not empty
            if (!empty($cached['url'])) {
                Log::info("VideoExtractor: Using cached result", ['url' => $embedUrl, 'cached_url' => $cached['url']]);
                return $cached;
            }
            // Cached failure - don't retry for a bit
            return null;
        }
        
        foreach (self::$extractors as $name => $extractorClass) {
            if ($extractorClass::isSupported($embedUrl)) {
                Log::info("VideoExtractor: Trying {$name}", ['url' => $embedUrl]);
                
                try {
                    $result = $extractorClass::extract($embedUrl);
                    
                    if ($result && !empty($result['url'])) {
                        Log::info("VideoExtractor: Success with {$name}", ['direct_url' => $result['url']]);
                        // Cache successful result for 20 minutes
                        Cache::put($cacheKey, $result, 1200);
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::error("VideoExtractor: Error with {$name}", [
                        'url' => $embedUrl,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Cache failure for 5 minutes to avoid repeated failed attempts
        Cache::put($cacheKey, ['url' => null], 300);
        Log::warning("VideoExtractor: All extractors failed", ['url' => $embedUrl]);
        
        return null;
    }
    
    /**
     * Check if URL can be extracted
     */
    public static function canExtract(string $url): bool
    {
        foreach (self::$extractors as $extractorClass) {
            if ($extractorClass::isSupported($url)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get list of supported hosts
     */
    public static function supportedHosts(): array
    {
        return array_keys(self::$extractors);
    }
    
    /**
     * Get extractor name for a URL
     */
    public static function getExtractorName(string $url): ?string
    {
        foreach (self::$extractors as $name => $extractorClass) {
            if ($extractorClass::isSupported($url)) {
                return $name;
            }
        }
        return null;
    }
    
    /**
     * Clear cached extraction result for a URL
     */
    public static function clearCache(string $embedUrl): void
    {
        $cacheKey = 'video_extract_result_' . md5($embedUrl);
        Cache::forget($cacheKey);
    }
}
