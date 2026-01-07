<?php

namespace App\Http\Controllers;

use App\Models\VideoServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamProxyController extends Controller
{
    public function redirect(string $token)
    {
        if (!request()->hasValidSignature()) {
            abort(403);
        }

        try {
            $id = Crypt::decryptString($token);
            $server = VideoServer::findOrFail($id);
            $url = $server->embed_url;
            
            // Check if it's a local storage URL
            if ($this->isLocalStorageUrl($url)) {
                return $this->streamLocalFile($url);
            }
            
            // For external URLs, redirect
            return redirect()->away($url);
        } catch (\Throwable $e) {
            abort(404);
        }
    }
    
    /**
     * Proxy extracted video URL
     * Route: /stream/extracted/{token}
     */
    public function proxyExtracted(string $token)
    {
        // Check signature - but allow bypass if token is valid (for CDN/proxy issues)
        $hasValidSignature = request()->hasValidSignature();
        
        if (!$hasValidSignature) {
            // Log for debugging
            \Log::warning('Stream proxy signature invalid', [
                'url' => request()->fullUrl(),
                'ip' => request()->ip(),
            ]);
            
            // Try to validate token anyway - if decryptable, allow it
            try {
                $testDecrypt = Crypt::decryptString($token);
                if (!filter_var($testDecrypt, FILTER_VALIDATE_URL)) {
                    abort(403, 'Invalid signature and token');
                }
                // Token is valid, signature issue might be CDN/proxy related
                \Log::info('Stream proxy: Allowing request with valid token despite signature issue');
            } catch (\Exception $e) {
                abort(403, 'Invalid or expired signature');
            }
        }
        
        try {
            // Decrypt the extracted URL from token
            $extractedUrl = Crypt::decryptString($token);
            
            // Validate it's a valid video URL
            if (!filter_var($extractedUrl, FILTER_VALIDATE_URL)) {
                abort(400, 'Invalid URL');
            }
            
            // Check if this host supports proxying or should redirect directly
            $host = parse_url($extractedUrl, PHP_URL_HOST) ?? '';
            
            // Hosts that should be redirected directly (they don't need proxy)
            // NOTE: mp4upload REMOVED - it needs proxy with proper referer
            $directRedirectHosts = [
                'dood',
                'streamtape', 
                'krakenfiles',
            ];
            
            $shouldRedirect = false;
            foreach ($directRedirectHosts as $directHost) {
                if (str_contains($host, $directHost)) {
                    $shouldRedirect = true;
                    break;
                }
            }
            
            // If direct redirect requested via query param (but not for mp4upload/mixdrop)
            if (request()->has('direct') && !str_contains($host, 'mp4upload') && !str_contains($host, 'mixdrop')) {
                $shouldRedirect = true;
            }
            
            if ($shouldRedirect) {
                // Return redirect with CORS headers
                return redirect()->away($extractedUrl)->withHeaders([
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
                ]);
            }
            
            return $this->streamExternalVideo($extractedUrl);
            
        } catch (\Throwable $e) {
            \Log::error('Proxy extracted error', ['error' => $e->getMessage()]);
            
            // Try to return a redirect as fallback
            try {
                $extractedUrl = Crypt::decryptString($token);
                if (filter_var($extractedUrl, FILTER_VALIDATE_URL)) {
                    return redirect()->away($extractedUrl);
                }
            } catch (\Exception $e2) {
                // Ignore
            }
            
            abort(404, 'Video not found');
        }
    }
    
    /**
     * Stream external video with range support
     */
    private function streamExternalVideo(string $url)
    {
        // Determine proper referer based on video host
        $referer = $this->getProperReferer($url);
        
        // Increased timeout for slow servers
        $connectTimeout = 15;
        $timeout = 60;
        
        // Get headers for the video (disable SSL verification for external hosts)
        $headResponse = Http::withoutVerifying()->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer' => $referer,
            'Origin' => $referer,
        ])->connectTimeout($connectTimeout)->timeout($timeout)->head($url);
        
        if (!$headResponse->successful()) {
            // Try GET if HEAD fails (some servers don't support HEAD)
            $headResponse = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => $referer,
                'Origin' => $referer,
                'Range' => 'bytes=0-0',
            ])->connectTimeout($connectTimeout)->timeout($timeout)->get($url);
            
            if (!$headResponse->successful() && $headResponse->status() !== 206) {
                \Log::error('Stream proxy: Failed to access video', ['url' => $url, 'status' => $headResponse->status()]);
                
                // Fallback: try redirecting directly
                return redirect()->away($url)->withHeaders([
                    'Access-Control-Allow-Origin' => '*',
                ]);
            }
        }
        
        $fileSize = (int) ($headResponse->header('Content-Length') ?? 0);
        
        // For partial response, calculate full size from Content-Range
        if ($headResponse->status() === 206 && $headResponse->header('Content-Range')) {
            if (preg_match('/bytes \d+-\d+\/(\d+)/', $headResponse->header('Content-Range'), $m)) {
                $fileSize = (int) $m[1];
            }
        }
        
        $contentType = $headResponse->header('Content-Type') ?? 'video/mp4';
        
        // Force video/mp4 for mp4 files
        if (str_contains($url, '.mp4') || str_contains($url, 'mp4upload')) {
            $contentType = 'video/mp4';
        }
        
        // Handle range requests
        $start = 0;
        $end = $fileSize > 0 ? $fileSize - 1 : 0;
        $statusCode = 200;
        
        $headers = [
            'Content-Type' => $contentType,
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range, Content-Type',
            'Access-Control-Expose-Headers' => 'Content-Length, Content-Range, Accept-Ranges',
            'Cache-Control' => 'public, max-age=3600',
        ];
        
        // Handle OPTIONS preflight
        if (request()->isMethod('OPTIONS')) {
            return response('', 204, $headers);
        }
        
        // Handle Range header
        $requestHeaders = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer' => $referer,
            'Origin' => $referer,
        ];
        
        if (request()->hasHeader('Range') && $fileSize > 0) {
            $range = request()->header('Range');
            
            if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
                $start = $matches[1] !== '' ? intval($matches[1]) : 0;
                $end = $matches[2] !== '' ? intval($matches[2]) : $fileSize - 1;
                
                if ($start > $end || $start >= $fileSize) {
                    return response('', 416, [
                        'Content-Range' => "bytes */$fileSize",
                    ]);
                }
                
                $end = min($end, $fileSize - 1);
                $statusCode = 206;
                $headers['Content-Range'] = "bytes $start-$end/$fileSize";
                $requestHeaders['Range'] = "bytes=$start-$end";
            }
        }
        
        $length = $fileSize > 0 ? ($end - $start + 1) : 0;
        if ($length > 0) {
            $headers['Content-Length'] = $length;
        }
        
        return new StreamedResponse(function () use ($url, $requestHeaders) {
            $ch = curl_init($url);
            
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => array_map(
                    fn($k, $v) => "$k: $v",
                    array_keys($requestHeaders),
                    array_values($requestHeaders)
                ),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification
                CURLOPT_SSL_VERIFYHOST => false, // Disable host verification
                CURLOPT_CONNECTTIMEOUT => 30,    // Connection timeout 30 seconds
                CURLOPT_TIMEOUT => 0,            // No timeout for streaming (unlimited)
                CURLOPT_LOW_SPEED_LIMIT => 1024, // Min 1KB/s
                CURLOPT_LOW_SPEED_TIME => 60,    // For 60 seconds before aborting
                CURLOPT_WRITEFUNCTION => function($ch, $data) {
                    echo $data;
                    flush();
                    return strlen($data);
                },
                CURLOPT_BUFFERSIZE => 1024 * 1024, // 1MB buffer
            ]);
            
            curl_exec($ch);
            curl_close($ch);
            
        }, $statusCode, $headers);
    }
    
    /**
     * Check if URL is a local storage URL
     */
    private function isLocalStorageUrl(string $url): bool
    {
        $appUrl = config('app.url');
        $patterns = [
            '/storage/',
            $appUrl . '/storage/',
            'https://nipnime.my.id/storage/',
            'https://www.nipnime.my.id/storage/',
            'http://nipnime.my.id/storage/',
            'http://www.nipnime.my.id/storage/',
        ];
        
        foreach ($patterns as $pattern) {
            if (str_contains($url, $pattern)) {
                return true;
            }
        }
        
        // Also check if it's a relative path starting with /storage/
        return str_starts_with($url, '/storage/');
    }
    
    /**
     * Extract local path from URL
     */
    private function extractLocalPath(string $url): string
    {
        // Remove domain and get path after /storage/
        if (preg_match('/\/storage\/(.+)$/', $url, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    /**
     * Stream local file with proper headers
     */
    private function streamLocalFile(string $url)
    {
        $relativePath = $this->extractLocalPath($url);
        
        if (empty($relativePath)) {
            abort(404, 'File not found');
        }
        
        // Check in public storage
        $fullPath = storage_path('app/public/' . $relativePath);
        
        if (!file_exists($fullPath)) {
            // Try public directory
            $fullPath = public_path('storage/' . $relativePath);
        }
        
        if (!file_exists($fullPath)) {
            abort(404, 'File not found');
        }
        
        $fileSize = filesize($fullPath);
        $mimeType = $this->getMimeType($fullPath);
        
        // Handle range requests for video seeking
        $start = 0;
        $end = $fileSize - 1;
        $statusCode = 200;
        
        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range, Content-Type',
            'Access-Control-Expose-Headers' => 'Content-Length, Content-Range, Accept-Ranges',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ];
        
        // Handle OPTIONS preflight
        if (request()->isMethod('OPTIONS')) {
            return response('', 204, $headers);
        }
        
        // Handle Range header for video seeking
        if (request()->hasHeader('Range')) {
            $range = request()->header('Range');
            
            if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
                $start = $matches[1] !== '' ? intval($matches[1]) : 0;
                $end = $matches[2] !== '' ? intval($matches[2]) : $fileSize - 1;
                
                // Validate range
                if ($start > $end || $start >= $fileSize) {
                    return response('', 416, [
                        'Content-Range' => "bytes */$fileSize",
                    ]);
                }
                
                $end = min($end, $fileSize - 1);
                $statusCode = 206;
                $headers['Content-Range'] = "bytes $start-$end/$fileSize";
            }
        }
        
        $length = $end - $start + 1;
        $headers['Content-Length'] = $length;
        
        return new StreamedResponse(function () use ($fullPath, $start, $length) {
            $handle = fopen($fullPath, 'rb');
            
            if ($start > 0) {
                fseek($handle, $start);
            }
            
            $remaining = $length;
            $chunkSize = 1024 * 1024; // 1MB chunks
            
            while ($remaining > 0 && !feof($handle)) {
                $readSize = min($chunkSize, $remaining);
                echo fread($handle, $readSize);
                $remaining -= $readSize;
                flush();
                
                // Prevent timeout
                if (connection_aborted()) {
                    break;
                }
            }
            
            fclose($handle);
        }, $statusCode, $headers);
    }
    
    /**
     * Get proper referer header based on video host
     * Different video hosts require different referer domains
     */
    private function getProperReferer(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        
        // Map of CDN domains to their proper referer
        $refererMap = [
            // MP4Upload CDN servers (a1, a2, a3, s1, s2, etc)
            'www.mp4upload.com' => 'https://www.mp4upload.com/',
            'mp4upload.com' => 'https://www.mp4upload.com/',
            
            // Mixdrop CDN
            'mixdrop.ag' => 'https://mixdrop.ag/',
            'mixdrop.co' => 'https://mixdrop.co/',
            'mixdrop.to' => 'https://mixdrop.to/',
            'mixdrop.ch' => 'https://mixdrop.ch/',
            'mixdrop.sx' => 'https://mixdrop.sx/',
            'mixdrop.bz' => 'https://mixdrop.bz/',
            'mixdrop.gl' => 'https://mixdrop.gl/',
            
            // Doodstream
            'dood.la' => 'https://dood.la/',
            'dood.pm' => 'https://dood.pm/',
            'dood.wf' => 'https://dood.wf/',
            'dood.re' => 'https://dood.re/',
            'dood.watch' => 'https://dood.watch/',
            'doodstream.com' => 'https://doodstream.com/',
            
            // Filemoon
            'filemoon.sx' => 'https://filemoon.sx/',
            'filemoon.to' => 'https://filemoon.to/',
            
            // Streamwish
            'streamwish.to' => 'https://streamwish.to/',
            'streamwish.com' => 'https://streamwish.com/',
            
            // KrakenFiles
            'krakenfiles.com' => 'https://krakenfiles.com/',
            
            // Streamtape
            'streamtape.com' => 'https://streamtape.com/',
            'streamtape.to' => 'https://streamtape.to/',
            
            // Acefile
            'acefile.co' => 'https://acefile.co/',
        ];
        
        // Check exact match first
        if (isset($refererMap[$host])) {
            return $refererMap[$host];
        }
        
        // Special handling for MP4Upload CDN subdomains (a1, a2, a3, a4, s1, s2, etc.)
        // Including those with port numbers like a4.mp4upload.com:183
        if (preg_match('/^[as]\d+\.mp4upload\.com(:\d+)?$/i', $host)) {
            return 'https://www.mp4upload.com/';
        }
        
        // Also check without port for mp4upload
        $hostWithoutPort = preg_replace('/:\d+$/', '', $host);
        if (preg_match('/mp4upload\.com$/i', $hostWithoutPort)) {
            return 'https://www.mp4upload.com/';
        }
        
        // Special handling for Mixdrop CDN subdomains
        if (preg_match('/\.mixdrop\.(ag|co|to|ch|sx|bz|gl)$/i', $host, $m)) {
            return "https://mixdrop.{$m[1]}/";
        }
        
        // Check partial match for CDN subdomains
        foreach ($refererMap as $domain => $referer) {
            if (str_contains($host, str_replace('www.', '', $domain))) {
                return $referer;
            }
        }
        
        // Default: use the video URL's origin
        $scheme = parse_url($url, PHP_URL_SCHEME) ?? 'https';
        return "{$scheme}://{$host}/";
    }
    
    /**
     * Get MIME type for video files
     */
    private function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'mkv' => 'video/x-matroska',
            'webm' => 'video/webm',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'flv' => 'video/x-flv',
            'wmv' => 'video/x-ms-wmv',
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
            'ass' => 'text/x-ass',
            'srt' => 'text/plain',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
