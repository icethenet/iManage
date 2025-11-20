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
     * Processes the uploaded image file.
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

        // 2. Validate file type and size
        // Support multiple possible config key names for backwards compatibility.
        $allowedMimes = $this->config['image']['allowed_mimes'] ?? [];
        $allowedExts = $this->config['image']['allowed_types'] ?? [];
        $maxSize = $this->config['image']['max_size'] ?? ($this->config['image']['max_file_size'] ?? 0);

        // Validate by MIME first (recommended), then fallback to extension check.
        $fileMime = $file['type'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $mimeOk = empty($allowedMimes) || in_array($fileMime, $allowedMimes, true);
        $extOk = empty($allowedExts) || in_array($fileExt, $allowedExts, true);

        if (!($mimeOk || $extOk)) {
            $errors[] = 'Invalid file type: ' . htmlspecialchars($fileMime) . '. Only JPG, PNG, GIF, and WEBP are allowed.';
        }

        if ($maxSize > 0 && $file['size'] > $maxSize) {
            $errors[] = 'File is too large. Maximum size is ' . ($maxSize / 1024 / 1024) . ' MB.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // 3. Prepare paths and new filename
        $originalName = $file['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $newFilename = pathinfo($originalName, PATHINFO_FILENAME) . '_' . uniqid() . '.' . $extension;

        $originalDir = $this->baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['original_dir'];
        $thumbDir = $this->baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['thumb_dir'];
        $pristineDir = $this->baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . 'pristine';
        $destinationPath = $originalDir . DIRECTORY_SEPARATOR . $newFilename;

        // 4. Create directories if they don't exist
        foreach ([$originalDir, $thumbDir, $pristineDir] as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0775, true)) {
                    $errors[] = "Failed to create directory: {$dir}. Check parent directory permissions.";
                    return ['success' => false, 'errors' => $errors];
                }
            }
        }

        // 5. Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            $errors[] = 'Failed to move uploaded file. This is likely a file permissions issue on the server in the \'public/uploads\' directory.';
            return ['success' => false, 'errors' => $errors];
        }

        // 6. Create a pristine backup immediately (so we have an original copy)
        $pristinePath = $pristineDir . DIRECTORY_SEPARATOR . $newFilename;
        if (!file_exists($pristinePath)) {
            // Try to copy; if it fails, log an error but continue (we don't want to fail the entire upload for a backup issue)
            try {
                if (!@copy($destinationPath, $pristinePath)) {
                    // If copy failed, attempt to change permissions and retry once
                    @chmod($pristineDir, 0775);
                    @copy($destinationPath, $pristinePath);
                }
            } catch (Exception $e) {
                // Non-fatal: continue but note that pristine wasn't created
                error_log('Warning: failed to create pristine backup for ' . $destinationPath . ' - ' . $e->getMessage());
            }
        }

        // 7. Create the thumbnail
        $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $newFilename;
        try {
            $manipulator = new ImageManipulator($destinationPath);
            $thumbWidth = $this->config['image']['thumb_width'] ?? ($this->config['image']['thumbnail_width'] ?? 200);
            $thumbHeight = $this->config['image']['thumb_height'] ?? ($this->config['image']['thumbnail_height'] ?? 200);
            $manipulator->thumbnail($thumbWidth, $thumbHeight);
            $manipulator->save($thumbPath);
        } catch (Exception $e) {
            $errors[] = 'Failed to create thumbnail: ' . $e->getMessage();
            // Clean up the original file if thumbnail fails
            if (file_exists($destinationPath)) {
                unlink($destinationPath);
            }
            return ['success' => false, 'errors' => $errors];
        }

        // 7. Get image dimensions
        list($width, $height) = getimagesize($destinationPath);

        return [
            'success'       => true,
            'filename'      => $newFilename,
            'original_name' => $originalName,
            'mime_type'     => $file['type'],
            'file_size'     => $file['size'],
            'width'         => $width,
            'height'        => $height,
        ];
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