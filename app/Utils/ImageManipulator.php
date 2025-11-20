<?php

/**
 * A utility class for performing common image manipulations using the GD library.
 *
 * Supports operations like resizing, cropping, creating thumbnails, rotating,
 * and applying various filters.
 */
class ImageManipulator {

    private $image;
    private $originalInfo;
    private $width;
    private $height;
    private $imageType;
    private $quality;

    /**
     * Constructor
     *
     * @param string $filename The path to the image file.
     * @param int $quality The default quality for saving images (1-100).
     * @throws Exception If the file does not exist or is not a valid image.
     */
    public function __construct(string $filename, int $quality = 85) {
        if (!file_exists($filename)) {
            throw new Exception("File not found: {$filename}");
        }

        $this->originalInfo = getimagesize($filename);
        if (!$this->originalInfo) {
            throw new Exception("Not a valid image file: {$filename}");
        }

        $this->width = $this->originalInfo[0];
        $this->height = $this->originalInfo[1];
        $this->imageType = $this->originalInfo[2];
        $this->quality = max(1, min(100, $quality));

        $this->image = $this->load($filename);
    }

    /**
     * Destructor to free up memory.
     */
    public function __destruct() {
        if (is_resource($this->image) || $this->image instanceof GdImage) {
            imagedestroy($this->image);
        }
    }

    /**
     * Loads an image from a file into a GD resource.
     *
     * @param string $filename
     * @return GdImage|resource|false
     */
    private function load(string $filename) {
        switch ($this->imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filename);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filename);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filename);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filename);
            default:
                throw new Exception("Unsupported image type.");
        }
    }

    /**
     * Saves the manipulated image to a file.
     *
     * @param string $filename The path to save the file to.
     * @param int|null $quality Overrides the default quality.
     * @return bool
     */
    public function save(string $filename, ?int $quality = null): bool {
        $quality = $quality ?? $this->quality;

        switch ($this->imageType) {
            case IMAGETYPE_JPEG:
                return imagejpeg($this->image, $filename, $quality);
            case IMAGETYPE_GIF:
                return imagegif($this->image, $filename);
            case IMAGETYPE_PNG:
                // PNG quality is compression level (0-9), so we convert from 1-100 scale.
                $pngQuality = round((100 - $quality) / 10);
                return imagepng($this->image, $filename, $pngQuality);
            case IMAGETYPE_WEBP:
                return imagewebp($this->image, $filename, $quality);
            default:
                return false;
        }
    }

    /**
     * Resizes the image to new dimensions.
     *
     * @param int $newWidth
     * @param int $newHeight
     * @param bool $maintainAspect
     */
    public function resize(int $newWidth, int $newHeight, bool $maintainAspect = true): void {
        if ($maintainAspect) {
            $ratio = $this->width / $this->height;
            if ($newWidth / $newHeight > $ratio) {
                $newWidth = $newHeight * $ratio;
            } else {
                $newHeight = $newWidth / $ratio;
            }
        }

        // Ensure integer dimensions for GD functions (avoid implicit float->int conversion warnings)
        $newWidth = (int) round($newWidth);
        $newHeight = (int) round($newHeight);

        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        $this->handleTransparency($newImage);
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, (int)$this->width, (int)$this->height);
        $this->image = $newImage;
        $this->width = $newWidth;
        $this->height = $newHeight;
    }

    /**
     * Creates a thumbnail by resizing and cropping to fit the exact dimensions.
     *
     * @param int $thumbWidth
     * @param int $thumbHeight
     */
    public function thumbnail(int $thumbWidth, int $thumbHeight): void {
        $originalRatio = $this->width / $this->height;
        $thumbRatio = $thumbWidth / $thumbHeight;

        if ($originalRatio >= $thumbRatio) {
            // Original is wider than or same ratio as thumbnail
            $newHeight = $thumbHeight;
            $newWidth = $this->width / ($this->height / $thumbHeight);
        } else {
            // Original is taller than thumbnail
            $newWidth = $thumbWidth;
            $newHeight = $this->height / ($this->width / $thumbWidth);
        }

        // Cast to float/int-safe values and delegate to resize (resize will cast to int)
        $this->resize((int) round($newWidth), (int) round($newHeight), true);
        $this->crop($thumbWidth, $thumbHeight);
    }

    /**
     * Crops the image from the center.
     *
     * @param int $cropWidth
     * @param int $cropHeight
     * @param int|null $x The x-coordinate of the crop start point.
     * @param int|null $y The y-coordinate of the crop start point.
     */
    public function crop(int $cropWidth, int $cropHeight, ?int $x = null, ?int $y = null): void {
        $x = $x ?? ($this->width - $cropWidth) / 2;
        $y = $y ?? ($this->height - $cropHeight) / 2;

        // Ensure integer coordinates for GD
        $x = (int) round($x);
        $y = (int) round($y);
        $cropWidth = (int) round($cropWidth);
        $cropHeight = (int) round($cropHeight);

        $newImage = imagecreatetruecolor($cropWidth, $cropHeight);
        $this->handleTransparency($newImage);
        imagecopy($newImage, $this->image, 0, 0, $x, $y, $cropWidth, $cropHeight);
        $this->image = $newImage;
        $this->width = $cropWidth;
        $this->height = $cropHeight;
    }

    /**
     * Rotates the image by a given number of degrees.
     *
     * @param float $degrees
     */
    public function rotate(float $degrees): void {
        $this->image = imagerotate($this->image, $degrees, 0);
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * Flips the image horizontally.
     */
    public function flipHorizontal(): void {
        imageflip($this->image, IMG_FLIP_HORIZONTAL);
    }

    /**
     * Flips the image vertically.
     */
    public function flipVertical(): void {
        imageflip($this->image, IMG_FLIP_VERTICAL);
    }

    /**
     * Converts the image to grayscale.
     */
    public function grayscale(): void {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
    }

    /**
     * Adjusts the brightness of the image.
     *
     * @param int $level Brightness level (-255 to 255).
     */
    public function brightness(int $level): void {
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $level);
    }

    /**
     * Adjusts the contrast of the image.
     *
     * @param int $level Contrast level (-100 to 100).
     */
    public function contrast(int $level): void {
        imagefilter($this->image, IMG_FILTER_CONTRAST, $level);
    }

    /**
     * Applies a blur effect to the image.
     */
    public function sharpen(): void {
        $matrix = [
            [0, -1, 0],
            [-1, 5, -1],
            [0, -1, 0],
        ];
        imageconvolution($this->image, $matrix, 1, 0);
    }

    /**
     * Applies a color overlay to the image.
     *
     * @param int $red Red value (0-255).
     * @param int $green Green value (0-255).
     * @param int $blue Blue value (0-255).
     * @param int $opacity Opacity percentage (0-100).
     */
    public function colorOverlay(int $red, int $green, int $blue, int $opacity): void {
        $width = imagesx($this->image);
        $height = imagesy($this->image);
        
        // Clamp values
        $red = max(0, min(255, $red));
        $green = max(0, min(255, $green));
        $blue = max(0, min(255, $blue));
        $opacity = max(0, min(100, $opacity));
        
        // Convert opacity percentage to alpha blend factor (0.0 to 1.0)
        $alpha = $opacity / 100.0;
        
        // Loop through each pixel and blend with overlay color
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($this->image, $x, $y);
                
                // Extract original RGB values
                $origRed = ($rgb >> 16) & 0xFF;
                $origGreen = ($rgb >> 8) & 0xFF;
                $origBlue = $rgb & 0xFF;
                
                // Blend colors
                $newRed = (int)($origRed * (1 - $alpha) + $red * $alpha);
                $newGreen = (int)($origGreen * (1 - $alpha) + $green * $alpha);
                $newBlue = (int)($origBlue * (1 - $alpha) + $blue * $alpha);
                
                // Set new color
                $newColor = imagecolorallocate($this->image, $newRed, $newGreen, $newBlue);
                imagesetpixel($this->image, $x, $y, $newColor);
            }
        }
    }

    /**
     * Preserves transparency for PNG and GIF images during manipulations.
     *
     * @param GdImage|resource $newImage The new image resource.
     */
    private function handleTransparency($newImage): void {
        if ($this->imageType == IMAGETYPE_GIF || $this->imageType == IMAGETYPE_PNG) {
            imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }
    }
}

?>