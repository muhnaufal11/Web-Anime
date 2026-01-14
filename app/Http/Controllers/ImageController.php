<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ImageController extends Controller
{
    /**
     * Serve optimized thumbnail image
     * URL: /img/{size}/{path}
     * Example: /img/200x300/posters/anime.jpg
     */
    public function thumbnail(Request $request, $size, $path)
    {
        // Parse size
        if (!preg_match('/^(\d+)x(\d+)$/', $size, $matches)) {
            return $this->placeholder();
        }
        
        $width = (int) $matches[1];
        $height = (int) $matches[2];
        
        // Limit sizes to prevent abuse
        $allowedSizes = ['64x96', '100x150', '200x300', '400x600'];
        
        if (!in_array($size, $allowedSizes)) {
            return $this->placeholder();
        }
        
        // Build file path
        $filePath = storage_path('app/public/' . $path);
        
        if (!file_exists($filePath)) {
            return $this->placeholder();
        }
        
        // Cache key for this thumbnail
        $cacheKey = "thumb_{$size}_" . md5($path);
        
        // Check if thumbnail exists in cache
        $thumbnailData = Cache::get($cacheKey);
        
        if (!$thumbnailData) {
            // Generate thumbnail
            $thumbnailData = $this->generateThumbnail($filePath, $width, $height);
            
            if ($thumbnailData) {
                // Cache for 7 days
                Cache::put($cacheKey, $thumbnailData, 604800);
            } else {
                // Fallback to original
                $thumbnailData = file_get_contents($filePath);
            }
        }
        
        return response($thumbnailData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=31536000, immutable')
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }
    
    private function placeholder()
    {
        return redirect(asset('images/placeholder.png'));
    }
    
    /**
     * Generate thumbnail from original image
     */
    private function generateThumbnail($filePath, $width, $height)
    {
        if (!extension_loaded('gd')) {
            return null;
        }
        
        $content = file_get_contents($filePath);
        $source = @imagecreatefromstring($content);
        
        if (!$source) {
            return null;
        }
        
        $origWidth = imagesx($source);
        $origHeight = imagesy($source);
        
        // Calculate crop dimensions (center crop to maintain aspect ratio)
        $targetRatio = $width / $height;
        $origRatio = $origWidth / $origHeight;
        
        if ($origRatio > $targetRatio) {
            $cropHeight = $origHeight;
            $cropWidth = (int)($origHeight * $targetRatio);
            $cropX = (int)(($origWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $origWidth;
            $cropHeight = (int)($origWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int)(($origHeight - $cropHeight) / 2);
        }
        
        // Create thumbnail
        $thumb = imagecreatetruecolor($width, $height);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        
        // Resample
        imagecopyresampled(
            $thumb, $source,
            0, 0, $cropX, $cropY,
            $width, $height, $cropWidth, $cropHeight
        );
        
        // Output to string
        ob_start();
        imagejpeg($thumb, null, 85);
        $data = ob_get_clean();
        
        imagedestroy($source);
        imagedestroy($thumb);
        
        return $data;
    }
}
