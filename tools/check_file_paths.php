<?php
$cfg = require __DIR__ . '/../config/app.php';
$projectRoot = dirname(__DIR__);
$baseUpload = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($cfg['upload_dir'], '/\\');
$user = 'integration_test_user';
$filename = $argv[1] ?? 'sample_691ccd5925e5d.png';
$pathSegment = $user; // default folder
$original = $baseUpload . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $cfg['original_dir'] . DIRECTORY_SEPARATOR . $filename;
$thumb = $baseUpload . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $cfg['thumb_dir'] . DIRECTORY_SEPARATOR . $filename;
$pristine = $baseUpload . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . 'pristine' . DIRECTORY_SEPARATOR . $filename;

echo "Checking files for: $filename\n";
foreach (['original'=>$original,'thumb'=>$thumb,'pristine'=>$pristine] as $k=>$p) {
    echo strtoupper($k).": $p\n";
    if (file_exists($p)) {
        echo "  Exists: YES\n";
        echo "  Size: " . filesize($p) . " bytes\n";
    } else {
        echo "  Exists: NO\n";
    }
}
