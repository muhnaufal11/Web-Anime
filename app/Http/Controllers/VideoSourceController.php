<?php

namespace App\Http\Controllers;

use App\Models\VideoServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class VideoSourceController extends Controller
{
    /**
     * Get video source URL (encrypted)
     */
    public function getSource(Request $request)
    {
        $serverId = $request->input('server');
        
        if (!$serverId) {
            return response()->json(['error' => 'Invalid request'], 400);
        }
        
        try {
            // Decrypt server ID
            $decryptedId = Crypt::decryptString($serverId);
            
            // Get video server
            $videoServer = VideoServer::findOrFail($decryptedId);
            
            $embedUrl = $videoServer->embed_url;
            $type = $this->getVideoType($embedUrl);
            
            // Return URL without proxification to ensure playback works
            return response()->json([
                'url' => $embedUrl,
                'type' => $type,
                'proxied' => false
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Not found'], 404);
        }
    }
    
    private function getVideoType($url)
    {
        if (str_contains($url, '<iframe')) {
            return 'iframe';
        } elseif (str_ends_with(strtolower($url), '.mp4')) {
            return 'mp4';
        } elseif (str_ends_with(strtolower($url), '.m3u8')) {
            return 'm3u8';
        }
        return 'url';
    }
}
