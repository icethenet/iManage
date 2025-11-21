<?php
// Simple PWA icon generator using GD.
// Run: php tools/generate_pwa_icons.php
// Creates: public/img/icons/icon-192.png, icon-512.png, icon-512-maskable.png

function makeIcon($size, $filename, $bgColor = [30,136,229], $text = 'i', $maskable = false) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    // Optional padding for maskable (safe area ~80%)
    $pad = $maskable ? (int)($size * 0.1) : 0;
    $bg = imagecolorallocate($img, $bgColor[0], $bgColor[1], $bgColor[2]);
    imagefilledrectangle($img, $pad, $pad, $size - $pad - 1, $size - $pad - 1, $bg);

    // Draw a simple white circle center accent
    $circleColor = imagecolorallocatealpha($img, 255, 255, 255, 30);
    $circleRadius = (int)($size * 0.35);
    imagefilledellipse($img, (int)($size/2), (int)($size/2), $circleRadius*2, $circleRadius*2, $circleColor);

    // Text rendering (fallback if TTF not available)
    $textColor = imagecolorallocate($img, 255, 255, 255);
    $font = 5; // Built-in font
    $textBoxW = imagefontwidth($font) * strlen($text);
    $textBoxH = imagefontheight($font);
    $tx = (int)(($size - $textBoxW)/2);
    $ty = (int)(($size - $textBoxH)/2);
    imagestring($img, $font, $tx, $ty, $text, $textColor);

    imagepng($img, $filename);
    imagedestroy($img);
}

$base = __DIR__ . '/../public/img/icons';
if (!is_dir($base)) {
    mkdir($base, 0775, true);
}

makeIcon(192, $base . '/icon-192.png');
makeIcon(512, $base . '/icon-512.png');
makeIcon(512, $base . '/icon-512-maskable.png', [30,136,229], 'i', true);

echo "PWA icons generated in public/img/icons\n";
?>