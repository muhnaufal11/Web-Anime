<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProcessVideoFaststart extends Command
{
    protected $signature = 'video:faststart {--all : Process all videos, not just new ones}';
    protected $description = 'Process MP4 videos to add faststart (moov atom at beginning) for faster streaming';

    public function handle()
    {
        $videoPath = storage_path('app/public/videos/episodes');
        
        if (!is_dir($videoPath)) {
            $this->error("Video directory not found: {$videoPath}");
            return 1;
        }

        $this->info("Scanning for videos in: {$videoPath}");
        
        $videos = glob($videoPath . '/*.mp4');
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($videos as $video) {
            $filename = basename($video);
            
            // Check if video already has moov at beginning
            if (!$this->option('all') && $this->hasFaststart($video)) {
                $this->line("  ✓ Skip (already faststart): {$filename}");
                $skipped++;
                continue;
            }

            $this->info("  Processing: {$filename}");
            
            if ($this->processVideo($video)) {
                $this->info("  ✓ Done: {$filename}");
                $processed++;
            } else {
                $this->error("  ✗ Failed: {$filename}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Processed: {$processed}");
        $this->info("Skipped (already faststart): {$skipped}");
        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        return 0;
    }

    /**
     * Check if video already has moov atom at the beginning
     */
    private function hasFaststart(string $filePath): bool
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        // Read first 50 bytes to check for moov atom
        $header = fread($handle, 50);
        fclose($handle);

        // Check if 'moov' appears early in the file (within first 50 bytes)
        // ftyp is always first (around byte 0-8), moov should be right after if faststart
        return strpos($header, 'moov') !== false;
    }

    /**
     * Process video with ffmpeg to add faststart
     */
    private function processVideo(string $filePath): bool
    {
        $tempFile = '/tmp/faststart_' . basename($filePath);
        
        // Run ffmpeg to add faststart
        $command = sprintf(
            'ffmpeg -i %s -c copy -movflags +faststart %s -y -loglevel error 2>&1',
            escapeshellarg($filePath),
            escapeshellarg($tempFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($tempFile)) {
            $this->error("FFmpeg error: " . implode("\n", $output));
            return false;
        }

        // Replace original with processed file
        if (!rename($tempFile, $filePath)) {
            @unlink($tempFile);
            return false;
        }

        return true;
    }
}
