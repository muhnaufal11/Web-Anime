<?php

namespace App\Http\Controllers;

use App\Models\VideoServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

            // Generate short-lived signed URL that maps to internal stream proxy
            $signedUrl = URL::temporarySignedRoute(
                'stream.proxy',
                now()->addMinutes(5),
                ['token' => Crypt::encryptString($videoServer->id)]
            );
            
            // For iframe/embed, we still return the proxied player page
            if ($type === 'html' || $type === 'url') {
                $playerUrl = URL::temporarySignedRoute(
                    'player.proxy',
                    now()->addMinutes(5),
                    ['token' => Crypt::encryptString($videoServer->id)]
                );
                return response()->json([
                    'url' => $playerUrl,
                    'type' => 'iframe',
                    'proxied' => true,
                ]);
            }

            return response()->json([
                'url' => $signedUrl,
                'type' => $type,
                'proxied' => true,
                'expires_in' => 300,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Not found'], 404);
        }
    }
    
    /**
     * Extract and return subtitle from MKV file
     * Uses FFmpeg on server to extract embedded subtitles
     */
    public function getSubtitle(string $token)
    {
        try {
            $decryptedId = Crypt::decryptString($token);
            $videoServer = VideoServer::findOrFail($decryptedId);
            $videoUrl = $videoServer->embed_url;
            
            Log::info('Subtitle request for video: ' . $videoUrl);
            
            // Check if it's an MKV file
            if (!str_ends_with(strtolower($videoUrl), '.mkv')) {
                return response()->json(['error' => 'Not an MKV file'], 400);
            }
            
            // Cache key for this video's subtitle
            $cacheKey = 'subtitle_vtt_' . md5($videoUrl);
            
            // Try to get from cache first
            $cachedSubtitle = Cache::get($cacheKey);
            if ($cachedSubtitle) {
                Log::info('Returning cached subtitle');
                return response()->json($cachedSubtitle);
            }
            
            // First: Try to detect subtitle format in the video
            $subtitleFormat = $this->detectSubtitleFormat($videoUrl);
            Log::info('Detected subtitle format: ' . ($subtitleFormat ?? 'unknown'));
            
            // Try VTT extraction first (most compatible with browsers)
            $vttSubtitle = $this->extractSubtitleAsVTT($videoUrl);
            if ($vttSubtitle && strlen(trim($vttSubtitle)) > 100) {
                $result = [
                    'subtitle' => $vttSubtitle,
                    'format' => 'vtt',
                ];
                
                // Cache for 1 hour
                Cache::put($cacheKey, $result, 3600);
                
                Log::info('Subtitle extracted successfully as VTT');
                return response()->json($result);
            }
            
            // Fallback: Extract ASS and convert to simple VTT
            if ($subtitleFormat === 'ass' || $subtitleFormat === null) {
                $assSubtitle = $this->extractSubtitleWithFFmpeg($videoUrl);
                if ($assSubtitle) {
                    // Convert ASS to simple VTT (strips complex styling)
                    $simpleVtt = $this->convertAssToVtt($assSubtitle);
                    if ($simpleVtt) {
                        $result = [
                            'subtitle' => $simpleVtt,
                            'format' => 'vtt',
                        ];
                        Cache::put($cacheKey, $result, 3600);
                        Log::info('Subtitle converted from ASS to VTT');
                        return response()->json($result);
                    }
                    
                    // If conversion fails, return raw ASS
                    $result = [
                        'subtitle' => $assSubtitle,
                        'format' => 'ass',
                    ];
                    Cache::put($cacheKey, $result, 3600);
                    Log::info('Subtitle extracted as raw ASS');
                    return response()->json($result);
                }
            }
            
            // No subtitle found
            Log::warning('No subtitle found for: ' . $videoUrl);
            return response()->json([
                'subtitle' => null,
                'tracks' => [],
                'message' => 'No embedded subtitle found'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Subtitle extraction error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to extract subtitle', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Convert ASS subtitle to simple VTT (strips complex styling)
     */
    private function convertAssToVtt(string $assContent): ?string
    {
        $lines = explode("\n", $assContent);
        $vtt = "WEBVTT\n\n";
        $dialogues = [];
        
        foreach ($lines as $line) {
            // Look for Dialogue lines
            if (preg_match('/^Dialogue:\s*\d+,([^,]+),([^,]+),[^,]*,[^,]*,\d+,\d+,\d+,[^,]*,(.+)$/i', trim($line), $matches)) {
                $start = $this->assTimeToVtt($matches[1]);
                $end = $this->assTimeToVtt($matches[2]);
                $text = $matches[3];
                
                // Strip ASS override tags
                $text = preg_replace('/\{[^}]*\}/', '', $text);
                
                // Convert ASS newline to VTT newline
                $text = str_replace('\N', "\n", $text);
                $text = str_replace('\n', "\n", $text);
                $text = str_replace('\\h', ' ', $text);
                
                // Clean up extra spaces
                $text = trim($text);
                
                if (!empty($text) && $start && $end) {
                    $dialogues[] = [
                        'start' => $start,
                        'end' => $end,
                        'text' => $text
                    ];
                }
            }
        }
        
        // Sort by start time
        usort($dialogues, function($a, $b) {
            return strcmp($a['start'], $b['start']);
        });
        
        // Build VTT content
        $counter = 1;
        foreach ($dialogues as $dialogue) {
            $vtt .= "{$counter}\n";
            $vtt .= "{$dialogue['start']} --> {$dialogue['end']}\n";
            $vtt .= "{$dialogue['text']}\n\n";
            $counter++;
        }
        
        return strlen($vtt) > 20 ? $vtt : null;
    }
    
    /**
     * Convert ASS time format (0:00:00.00) to VTT format (00:00:00.000)
     */
    private function assTimeToVtt(string $assTime): ?string
    {
        $assTime = trim($assTime);
        
        // ASS format: H:MM:SS.CC (centiseconds)
        if (preg_match('/^(\d+):(\d{2}):(\d{2})\.(\d{2})$/', $assTime, $matches)) {
            $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mins = $matches[2];
            $secs = $matches[3];
            $centis = $matches[4];
            $millis = str_pad($centis . '0', 3, '0', STR_PAD_RIGHT);
            
            return "{$hours}:{$mins}:{$secs}.{$millis}";
        }
        
        return null;
    }
    
    /**
     * Extract subtitle as WebVTT format
     */
    private function extractSubtitleAsVTT(string $videoUrl): ?string
    {
        $ffmpegPath = $this->getFFmpegPath();
        if (!$ffmpegPath) {
            return null;
        }
        
        try {
            $inputPath = $this->resolveVideoPath($videoUrl);
            Log::info('Extracting VTT subtitle from: ' . $inputPath);
            
            if (!file_exists($inputPath)) {
                Log::error('Video file not found: ' . $inputPath);
                return null;
            }
            
            $tempFile = tempnam(sys_get_temp_dir(), 'sub_') . '.vtt';
            
            // Extract as WebVTT directly
            $command = sprintf(
                '%s -i %s -map 0:s:0 -c:s webvtt %s -y 2>&1',
                escapeshellcmd($ffmpegPath),
                escapeshellarg($inputPath),
                escapeshellarg($tempFile)
            );
            
            Log::info('FFmpeg VTT command: ' . $command);
            
            exec($command, $output, $returnCode);
            
            Log::info('FFmpeg VTT exit code: ' . $returnCode);
            
            if (file_exists($tempFile) && filesize($tempFile) > 50) {
                $content = file_get_contents($tempFile);
                unlink($tempFile);
                return $content;
            }
            
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('VTT extraction error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Extract subtitle from MKV using FFmpeg
     */
    private function extractSubtitleWithFFmpeg(string $videoUrl): ?string
    {
        // Check if FFmpeg is available
        $ffmpegPath = $this->getFFmpegPath();
        if (!$ffmpegPath) {
            Log::warning('FFmpeg not found on system');
            return null;
        }
        
        try {
            // Convert storage URL to local path if needed
            $inputPath = $this->resolveVideoPath($videoUrl);
            
            Log::info('Extracting subtitle from: ' . $inputPath);
            
            // Create a temporary file for subtitle output
            $tempFile = tempnam(sys_get_temp_dir(), 'sub_') . '.ass';
            
            // FFmpeg command to extract first subtitle track as ASS
            // -i: input file (can be URL or local path)
            // -map 0:s:0: select first subtitle stream
            // -c:s ass: convert to ASS format
            $command = sprintf(
                '%s -i %s -map 0:s:0 -c:s ass %s -y 2>&1',
                escapeshellcmd($ffmpegPath),
                escapeshellarg($inputPath),
                escapeshellarg($tempFile)
            );
            
            Log::info('FFmpeg command: ' . $command);
            
            // Set timeout for remote files
            $timeout = 60;
            
            // Execute FFmpeg with timeout
            $output = [];
            $returnCode = 0;
            
            // Use proc_open for better control
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            
            $process = proc_open($command, $descriptors, $pipes);
            
            if (is_resource($process)) {
                fclose($pipes[0]);
                
                // Set non-blocking
                stream_set_blocking($pipes[1], false);
                stream_set_blocking($pipes[2], false);
                
                $startTime = time();
                $stderr = '';
                while (true) {
                    $status = proc_get_status($process);
                    if (!$status['running']) {
                        break;
                    }
                    $stderr .= fread($pipes[2], 8192);
                    if (time() - $startTime > $timeout) {
                        proc_terminate($process);
                        Log::warning('FFmpeg timeout for: ' . $inputPath);
                        break;
                    }
                    usleep(100000); // 100ms
                }
                
                $stderr .= stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);
                
                Log::info('FFmpeg exit code: ' . $exitCode);
                if ($exitCode !== 0) {
                    Log::warning('FFmpeg stderr: ' . substr($stderr, -500));
                }
            }
            
            // Check if subtitle file was created and has content
            if (file_exists($tempFile) && filesize($tempFile) > 100) {
                $subtitleContent = file_get_contents($tempFile);
                unlink($tempFile); // Clean up
                Log::info('Subtitle extracted successfully, size: ' . strlen($subtitleContent));
                return $subtitleContent;
            }
            
            // Clean up temp file if exists
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            Log::warning('No subtitle file created or file is too small');
            return null;
            
        } catch (\Exception $e) {
            Log::error('FFmpeg extraction error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Resolve video URL to local path if it's a local storage URL
     */
    private function resolveVideoPath(string $url): string
    {
        // Check if it's a local storage URL
        $patterns = [
            '/\/storage\/(.+)$/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $relativePath = $matches[1];
                
                // Try storage/app/public first
                $localPath = storage_path('app/public/' . $relativePath);
                if (file_exists($localPath)) {
                    return $localPath;
                }
                
                // Try public/storage
                $publicPath = public_path('storage/' . $relativePath);
                if (file_exists($publicPath)) {
                    return $publicPath;
                }
            }
        }
        
        // Return original URL if not a local file
        return $url;
    }
    
    /**
     * Get FFmpeg path
     */
    private function getFFmpegPath(): ?string
    {
        // Check common locations
        $paths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'ffmpeg', // System PATH
        ];
        
        foreach ($paths as $path) {
            if ($path === 'ffmpeg') {
                // Check if available in PATH
                $output = shell_exec('which ffmpeg 2>/dev/null') ?? shell_exec('where ffmpeg 2>nul');
                if ($output) {
                    return trim($output);
                }
            } elseif (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Detect subtitle format in video file using FFprobe
     */
    private function detectSubtitleFormat(string $videoUrl): ?string
    {
        $ffprobePath = $this->getFFprobePath();
        if (!$ffprobePath) {
            return null;
        }
        
        try {
            $inputPath = $this->resolveVideoPath($videoUrl);
            
            // Use ffprobe to get subtitle stream info
            $command = sprintf(
                '%s -v quiet -select_streams s:0 -show_entries stream=codec_name -of csv=p=0 %s 2>&1',
                escapeshellcmd($ffprobePath),
                escapeshellarg($inputPath)
            );
            
            $output = shell_exec($command);
            $codecName = trim($output ?? '');
            
            Log::info('FFprobe subtitle codec: ' . $codecName);
            
            // Map codec names to formats
            if (in_array($codecName, ['ass', 'ssa'])) {
                return 'ass';
            } elseif (in_array($codecName, ['subrip', 'srt'])) {
                return 'srt';
            } elseif ($codecName === 'webvtt') {
                return 'vtt';
            }
            
            return $codecName ?: null;
        } catch (\Exception $e) {
            Log::error('FFprobe detection error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get FFprobe path
     */
    private function getFFprobePath(): ?string
    {
        $paths = [
            '/usr/bin/ffprobe',
            '/usr/local/bin/ffprobe',
            'ffprobe',
        ];
        
        foreach ($paths as $path) {
            if ($path === 'ffprobe') {
                $output = shell_exec('which ffprobe 2>/dev/null') ?? shell_exec('where ffprobe 2>nul');
                if ($output) {
                    return trim($output);
                }
            } elseif (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    private function getVideoType($url)
    {
        if (str_contains($url, '<iframe')) {
            return 'iframe';
        } elseif (str_ends_with(strtolower($url), '.mp4')) {
            return 'mp4';
        } elseif (str_ends_with(strtolower($url), '.mkv')) {
            return 'mkv';
        } elseif (str_ends_with(strtolower($url), '.m3u8')) {
            return 'm3u8';
        }
        return 'url';
    }
}
