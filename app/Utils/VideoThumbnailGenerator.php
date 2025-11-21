<?php

/**
 * Generates thumbnail images from video files using FFmpeg.
 * 
 * This class extracts a frame from a video file at a specified timestamp
 * and creates a thumbnail image for gallery display.
 */
class VideoThumbnailGenerator {
    
    private $ffmpegPath;
    private $ffprobePath;
    
    public function __construct() {
        // Try to detect FFmpeg path
        $this->ffmpegPath = $this->detectFFmpeg();
        $this->ffprobePath = $this->detectFFprobe();
    }
    
    /**
     * Check if FFmpeg is available on the system
     * 
     * @return bool
     */
    public function isAvailable(): bool {
        return !empty($this->ffmpegPath);
    }
    
    /**
     * Detect FFmpeg executable path
     * 
     * @return string|null
     */
    private function detectFFmpeg(): ?string {
        // Try common paths
        $paths = [
            'ffmpeg', // In PATH
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
        ];
        
        foreach ($paths as $path) {
            if ($this->commandExists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Detect FFprobe executable path
     * 
     * @return string|null
     */
    private function detectFFprobe(): ?string {
        // Try common paths
        $paths = [
            'ffprobe', // In PATH
            '/usr/bin/ffprobe',
            '/usr/local/bin/ffprobe',
            'C:\\ffmpeg\\bin\\ffprobe.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffprobe.exe',
        ];
        
        foreach ($paths as $path) {
            if ($this->commandExists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Check if a command exists
     * 
     * @param string $command
     * @return bool
     */
    private function commandExists(string $command): bool {
        $test = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $output = [];
        $return = 0;
        exec("$test " . escapeshellarg($command) . " 2>&1", $output, $return);
        return $return === 0;
    }
    
    /**
     * Get video duration in seconds
     * 
     * @param string $videoPath Absolute path to video file
     * @return float|null Duration in seconds or null if failed
     */
    public function getVideoDuration(string $videoPath): ?float {
        if (!$this->ffprobePath || !file_exists($videoPath)) {
            return null;
        }
        
        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($videoPath)
        );
        
        $output = [];
        exec($command, $output, $return);
        
        if ($return === 0 && !empty($output[0])) {
            return (float)$output[0];
        }
        
        return null;
    }
    
    /**
     * Generate a thumbnail from a video file
     * 
     * @param string $videoPath Absolute path to the video file
     * @param string $thumbnailPath Absolute path where thumbnail should be saved
     * @param int $timestamp Timestamp in seconds to extract frame (default: 1 second)
     * @param int $width Thumbnail width (default: 300)
     * @param int $height Thumbnail height (default: 300, maintains aspect ratio if null)
     * @return bool True on success, false on failure
     */
    public function generateThumbnail(
        string $videoPath,
        string $thumbnailPath,
        int $timestamp = 1,
        int $width = 300,
        ?int $height = null
    ): bool {
        if (!$this->isAvailable()) {
            error_log('FFmpeg not available for video thumbnail generation');
            return false;
        }
        
        if (!file_exists($videoPath)) {
            error_log("Video file not found: $videoPath");
            return false;
        }
        
        // Ensure output directory exists
        $outputDir = dirname($thumbnailPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Get video duration to ensure timestamp is valid
        $duration = $this->getVideoDuration($videoPath);
        if ($duration !== null && $timestamp >= $duration) {
            // If requested timestamp is beyond video duration, use midpoint
            $timestamp = (int)($duration / 2);
        }
        
        // Build FFmpeg command
        $scaleFilter = $height ? "-vf \"scale=$width:$height:force_original_aspect_ratio=decrease,pad=$width:$height:(ow-iw)/2:(oh-ih)/2\"" : "-vf \"scale=$width:-1\"";
        
        $command = sprintf(
            '%s -ss %d -i %s -frames:v 1 %s -y %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            $timestamp,
            escapeshellarg($videoPath),
            $scaleFilter,
            escapeshellarg($thumbnailPath)
        );
        
        $output = [];
        $return = 0;
        exec($command, $output, $return);
        
        if ($return !== 0) {
            error_log("FFmpeg thumbnail generation failed: " . implode("\n", $output));
            return false;
        }
        
        return file_exists($thumbnailPath);
    }
    
    /**
     * Get video dimensions
     * 
     * @param string $videoPath Absolute path to video file
     * @return array|null Array with 'width' and 'height' keys, or null if failed
     */
    public function getVideoDimensions(string $videoPath): ?array {
        if (!$this->ffprobePath || !file_exists($videoPath)) {
            return null;
        }
        
        $command = sprintf(
            '%s -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($videoPath)
        );
        
        $output = [];
        exec($command, $output, $return);
        
        if ($return === 0 && !empty($output[0])) {
            $parts = explode('x', $output[0]);
            if (count($parts) === 2) {
                return [
                    'width' => (int)$parts[0],
                    'height' => (int)$parts[1]
                ];
            }
        }
        
        return null;
    }
}
