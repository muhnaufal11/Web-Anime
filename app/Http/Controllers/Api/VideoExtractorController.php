<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VideoExtractor\VideoExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VideoExtractorController extends Controller
{
    /**
     * Extract direct video URL from embed URL
     */
    public function extract(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);
        
        $embedUrl = $request->input('url');
        
        // Rate limit per IP
        $ip = $request->ip();
        $rateLimitKey = "video_extract_rate_{$ip}";
        $attempts = Cache::get($rateLimitKey, 0);
        
        if ($attempts > 30) { // Max 30 requests per minute
            return response()->json([
                'success' => false,
                'error' => 'Too many requests. Please wait.',
            ], 429);
        }
        
        Cache::put($rateLimitKey, $attempts + 1, 60);
        
        // Check if URL can be extracted
        if (!VideoExtractorService::canExtract($embedUrl)) {
            return response()->json([
                'success' => false,
                'error' => 'Unsupported video host',
                'supported_hosts' => VideoExtractorService::supportedHosts(),
            ]);
        }
        
        // Try to extract
        $result = VideoExtractorService::extract($embedUrl);
        
        if ($result) {
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        }
        
        return response()->json([
            'success' => false,
            'error' => 'Could not extract video URL. The host may have updated their protection.',
        ]);
    }
    
    /**
     * Get supported hosts
     */
    public function supportedHosts()
    {
        return response()->json([
            'hosts' => VideoExtractorService::supportedHosts(),
        ]);
    }
}
