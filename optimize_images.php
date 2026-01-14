<?php

/**
 * Script untuk mengoptimasi gambar di storage
 * Jalankan: php optimize_images.php
 */

$storagePath = __DIR__ . '/storage/app/public';
$quality = 80;
$maxWidth = 400;  // Untuk poster anime
$maxHeight = 600;

echo "🖼️  Image Optimization Script\n";
echo "==============================\n\n";

// Check if GD extension available
if (!extension_loaded('gd')) {
    echo "❌ GD extension not loaded. Please install php-gd.\n";
    exit(1);
}

function getImageInfo($path) {
    $info = getimagesize($path);
    if (!$info) return null;
    return [
        'width' => $info[0],
        'height' => $info[1],
        'type' => $info[2],
        'mime' => $info['mime']
    ];
}

function optimizeImage($sourcePath, $maxWidth, $maxHeight, $quality) {
    $info = getImageInfo($sourcePath);
    if (!$info) return false;

    $originalSize = filesize($sourcePath);
    
    // Load image based on type
    switch ($info['type']) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$source) return false;
    
    $width = $info['width'];
    $height = $info['height'];
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);
    
    // Skip if already optimal size
    if ($ratio >= 1 && $originalSize < 100000) {
        imagedestroy($source);
        return false;
    }
    
    // Create resized image
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG
    if ($info['type'] == IMAGETYPE_PNG) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
        imagefill($destination, 0, 0, $transparent);
    }
    
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save as JPEG for smaller size (except PNG with transparency)
    if ($info['type'] == IMAGETYPE_PNG) {
        imagepng($destination, $sourcePath, 8);
    } else {
        imagejpeg($destination, $sourcePath, $quality);
    }
    
    imagedestroy($source);
    imagedestroy($destination);
    
    $newSize = filesize($sourcePath);
    $saved = $originalSize - $newSize;
    
    return [
        'original' => $originalSize,
        'new' => $newSize,
        'saved' => $saved,
        'percent' => round(($saved / $originalSize) * 100, 1)
    ];
}

function scanDirectory($dir, $maxWidth, $maxHeight, $quality) {
    $totalSaved = 0;
    $optimized = 0;
    $skipped = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $path = $file->getPathname();
                $relativePath = str_replace($dir, '', $path);
                
                echo "Processing: $relativePath... ";
                
                $result = optimizeImage($path, $maxWidth, $maxHeight, $quality);
                
                if ($result) {
                    echo "✓ Saved " . round($result['saved'] / 1024, 1) . "KB ({$result['percent']}%)\n";
                    $totalSaved += $result['saved'];
                    $optimized++;
                } else {
                    echo "⏭️  Skipped (already optimal)\n";
                    $skipped++;
                }
            }
        }
    }
    
    return [
        'optimized' => $optimized,
        'skipped' => $skipped,
        'totalSaved' => $totalSaved
    ];
}

// Check if storage path exists
if (!is_dir($storagePath)) {
    echo "❌ Storage path not found: $storagePath\n";
    exit(1);
}

// Scan anime posters directory
$postersPath = $storagePath . '/anime-posters';
if (is_dir($postersPath)) {
    echo "📁 Scanning: anime-posters\n\n";
    $result = scanDirectory($postersPath, $maxWidth, $maxHeight, $quality);
    
    echo "\n==============================\n";
    echo "✅ Optimized: {$result['optimized']} images\n";
    echo "⏭️  Skipped: {$result['skipped']} images\n";
    echo "💾 Total saved: " . round($result['totalSaved'] / 1024 / 1024, 2) . " MB\n";
} else {
    echo "⚠️  anime-posters directory not found\n";
}

// Scan all storage
echo "\n📁 Scanning: full storage\n\n";
$result = scanDirectory($storagePath, 800, 1200, $quality);

echo "\n==============================\n";
echo "✅ Total Optimized: {$result['optimized']} images\n";
echo "⏭️  Total Skipped: {$result['skipped']} images\n";
echo "💾 Total saved: " . round($result['totalSaved'] / 1024 / 1024, 2) . " MB\n";
echo "\n✨ Done!\n";
