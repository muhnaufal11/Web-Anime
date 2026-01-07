#!/bin/sh
# Fix MP4 files for web streaming by moving moov atom to the beginning

VIDEO_DIR="/app/storage/app/public/videos/episodes"
TEMP_DIR="/tmp/video_fix"

mkdir -p "$TEMP_DIR"

echo "Starting faststart fix for all MP4 files..."
echo "============================================="

for video in "$VIDEO_DIR"/*.mp4; do
    if [ -f "$video" ]; then
        filename=$(basename "$video")
        echo ""
        echo "Processing: $filename"
        
        # Check if already has moov at start
        if head -c 100 "$video" | grep -q "moov"; then
            echo "  -> Already optimized, skipping"
            continue
        fi
        
        # Create faststart version
        echo "  -> Converting with faststart..."
        ffmpeg -i "$video" -c copy -movflags +faststart "$TEMP_DIR/$filename" -y -loglevel error
        
        if [ $? -eq 0 ] && [ -f "$TEMP_DIR/$filename" ]; then
            # Replace original with optimized version
            mv "$TEMP_DIR/$filename" "$video"
            echo "  -> Done!"
        else
            echo "  -> ERROR: Failed to process"
        fi
    fi
done

echo ""
echo "============================================="
echo "All videos processed!"
rm -rf "$TEMP_DIR"
