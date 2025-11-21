<?php

/**
 * Handles the processing of uploaded image files.
 *
 * This class is responsible for validating, moving, and creating thumbnails
 * for uploaded images. It is designed to be robust and provide clear error
 * messages.
 */
class ImageUploadHandler {

    private $config;
    private $baseUploadPath;

    public function __construct() {
        $appConfigPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
        $this->config = require $appConfigPath;

        // The absolute base path to the public uploads directory (centralized helper)
        if (class_exists('Path')) {
            $this->baseUploadPath = Path::uploadsBaseFs();
        } else {
            $this->baseUploadPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($this->config['upload_dir'], '/');
        }
    }

    /**
     * Processes the uploaded file (image or video).
     *
     * @param array $file The $_FILES['image'] array.
     * @param string $pathSegment The user- and folder-specific path (e.g., "John/Asian Girls").
     * @return array An array containing the result of the upload.
     */
    public function processUpload(array $file, string $pathSegment): array {
        $errors = [];

        // 1. Validate upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
            return ['success' => false, 'errors' => $errors];
        }

        // 2. Detect file type (image or video)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);
        $fileType = $this->detectFileType($detectedMime);
        
        if (!$fileType) {
            return ['success' => false, 'errors' => ['Unsupported file type. Only images and videos are allowed.']];
        }

        // 3. Validate file type and size based on detected type
        if (!isset($this->config[$fileType])) {
            return ['success' => false, 'errors' => ['File type configuration not found.']];
        }
        
        $config = $this->config[$fileType];
        $fileMime = $file['type'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        $allowedMimes = $config['allowed_mimes'] ?? [];
        $allowedExts = $config['allowed_types'] ?? [];
        $maxSize = $config['max_file_size'] ?? 0;

        // For images, verify magic bytes for security
        if ($fileType === 'image') {
            $safeMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($detectedMime, $safeMimeTypes, true)) {
                $errors[] = 'File content does not match allowed image types. Detected: ' . htmlspecialchars($detectedMime);
            }
        } else if ($fileType === 'video') {
            // For videos, verify magic bytes are valid video format
            $safeVideoMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm'];
            if (!in_array($detectedMime, $safeVideoMimes, true)) {
                $errors[] = 'File content does not match allowed video types. Detected: ' . htmlspecialchars($detectedMime);
            }
        }

        $mimeOk = empty($allowedMimes) || in_array($detectedMime, $allowedMimes, true);
        $extOk = empty($allowedExts) || in_array($fileExt, $allowedExts, true);

        if (!($mimeOk || $extOk)) {
            $errors[] = 'Invalid file type: ' . htmlspecialchars($fileMime) . ' (detected: ' . htmlspecialchars($detectedMime) . ')';
        }

        if ($maxSize > 0 && $file['size'] > $maxSize) {
            $errors[] = 'File is too large. Maximum size is ' . ($maxSize / 1024 / 1024) . ' MB.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // 4. Prepare paths and new filename
        $originalName = $file['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $newFilename = pathinfo($originalName, PATHINFO_FILENAME) . '_' . uniqid() . '.' . $extension;

        $originalDir = $this->baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['original_dir'];
        $thumbDir = $this->baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['thumb_dir'];
        $pristineDir = $this->baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . 'pristine';
        $destinationPath = $originalDir . DIRECTORY_SEPARATOR . $newFilename;

        // 5. Create directories if they don't exist
        foreach ([$originalDir, $thumbDir, $pristineDir] as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0775, true)) {
                    $errors[] = "Failed to create directory: {$dir}. Check parent directory permissions.";
                    return ['success' => false, 'errors' => $errors];
                }
            }
        }

        // 6. Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            $errors[] = 'Failed to move uploaded file. This is likely a file permissions issue on the server in the \'public/uploads\' directory.';
            return ['success' => false, 'errors' => $errors];
        }

        // 7. Create a pristine backup immediately
        $pristinePath = $pristineDir . DIRECTORY_SEPARATOR . $newFilename;
        if (!file_exists($pristinePath)) {
            try {
                if (!@copy($destinationPath, $pristinePath)) {
                    @chmod($pristineDir, 0775);
                    @copy($destinationPath, $pristinePath);
                }
            } catch (Exception $e) {
                error_log('Warning: failed to create pristine backup for ' . $destinationPath . ' - ' . $e->getMessage());
            }
        }

        // 8. Create the thumbnail
        $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $newFilename;
        $thumbnailFilename = $newFilename;
        
        // For videos, generate JPG thumbnail
        if ($fileType === 'video') {
            $thumbnailFilename = pathinfo($newFilename, PATHINFO_FILENAME) . '.jpg';
            $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $thumbnailFilename;
        }
        
        try {
            if ($fileType === 'image') {
                // Image thumbnail using ImageManipulator
                $manipulator = new ImageManipulator($destinationPath);
                $thumbWidth = $config['thumbnail_width'] ?? 200;
                $thumbHeight = $config['thumbnail_height'] ?? 200;
                $manipulator->thumbnail($thumbWidth, $thumbHeight);
                $manipulator->save($thumbPath);
            } else {
                // Video thumbnail using FFmpeg
                require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'VideoThumbnailGenerator.php';
                $videoThumb = new VideoThumbnailGenerator();
                
                if (!$videoThumb->isAvailable()) {
                    // FFmpeg not available - create a placeholder thumbnail
                    $this->createPlaceholderThumbnail($thumbPath, 'VIDEO');
                } else {
                    $timestamp = $config['thumbnail_timestamp'] ?? 1;
                    $thumbWidth = $config['thumbnail_width'] ?? 200;
                    $thumbHeight = $config['thumbnail_height'] ?? 200;
                    
                    if (!$videoThumb->generateThumbnail($destinationPath, $thumbPath, $timestamp, $thumbWidth, $thumbHeight)) {
                        // Fallback to placeholder if generation fails
                        $this->createPlaceholderThumbnail($thumbPath, 'VIDEO');
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to create thumbnail: ' . $e->getMessage();
            // Clean up the original file if thumbnail fails
            if (file_exists($destinationPath)) {
                unlink($destinationPath);
            }
            return ['success' => false, 'errors' => $errors];
        }

        // 9. Get file dimensions
        $width = 0;
        $height = 0;
        
        if ($fileType === 'image') {
            list($width, $height) = getimagesize($destinationPath);
        } else {
            // Try to get video dimensions
            require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'VideoThumbnailGenerator.php';
            $videoThumb = new VideoThumbnailGenerator();
            if ($videoThumb->isAvailable()) {
                $dimensions = $videoThumb->getVideoDimensions($destinationPath);
                if ($dimensions) {
                    $width = $dimensions['width'];
                    $height = $dimensions['height'];
                }
            }
        }

        return [
            'success'       => true,
            'filename'      => $newFilename,
            'original_name' => $originalName,
            'mime_type'     => $detectedMime,
            'file_size'     => $file['size'],
            'width'         => $width,
            'height'        => $height,
            'file_type'     => $fileType,
            'thumbnail'     => $thumbnailFilename, // May differ from filename for videos
        ];
    }
    
    /**
     * Detect if uploaded file is image or video
     * 
     * @param string $mimeType
     * @return string|null 'image' or 'video' or null if unsupported
     */
    private function detectFileType(string $mimeType): ?string {
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        }
        
        if (strpos($mimeType, 'video/') === 0 && !empty($this->config['video']['enabled'])) {
            return 'video';
        }
        
        return null;
    }
    
    /**
     * Create a simple placeholder thumbnail for videos
     * 
     * @param string $outputPath
     * @param string $text
     */
    private function createPlaceholderThumbnail(string $outputPath, string $text = 'VIDEO'): void {
        $width = 200;
        $height = 200;
        
        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, 45, 45, 45);
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        imagefill($image, 0, 0, $bgColor);
        
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $fontSize, $x, $y, $text, $textColor);
        
        imagejpeg($image, $outputPath, 85);
        imagedestroy($image);
    }

    public function deleteImage(string $filename, string $pathSegment): void {
        $originalPath = $this->baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['original_dir'] . DIRECTORY_SEPARATOR . $filename;
        $thumbPath = $this->baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['thumb_dir'] . DIRECTORY_SEPARATOR . $filename;
        $pristinePath = $this->baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . 'pristine' . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($originalPath)) {
            unlink($originalPath);
        }
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        if (file_exists($pristinePath)) {
            unlink($pristinePath);
        }
    }

    private function getUploadErrorMessage(int $errorCode): string {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder on the server.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk. Check server permissions.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'An unknown upload error occurred.';
        }
    }
}

?>