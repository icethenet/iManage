<?php
/**
 * Regenerate thumbnails and pristine backups for images belonging to a username.
 * Usage: php regenerate_thumbs_pristine.php <username>
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($argc < 2) {
    echo "Usage: php regenerate_thumbs_pristine.php <username>\n";
    exit(1);
}
$username = $argv[1];

$config = require __DIR__ . '/../config/app.php';
$dbcfg = require __DIR__ . '/../config/database.php';
$projectRoot = dirname(__DIR__);
$baseUpload = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($config['upload_dir'], '/\\');

try {
    $pdo = new PDO('mysql:host='.$dbcfg['host'].';dbname='.$dbcfg['database'].';charset='.$dbcfg['charset'], $dbcfg['username'], $dbcfg['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT id, filename, folder FROM images WHERE user_id = (SELECT id FROM users WHERE username = ?)');
    $stmt->execute([$username]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "No images found for user '{$username}'\n";
        exit(0);
    }

    foreach ($rows as $r) {
        $folder = $r['folder'] ?: 'default';
        $pathSegment = ($folder && $folder !== 'default') ? $username . DIRECTORY_SEPARATOR . $folder : $username;
        $originalDir = $baseUpload . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $config['original_dir'];
        $thumbDir = $baseUpload . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $config['thumb_dir'];
        $pristineDir = $baseUpload . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . 'pristine';

        $originalPath = $originalDir . DIRECTORY_SEPARATOR . $r['filename'];
        $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $r['filename'];
        $pristinePath = $pristineDir . DIRECTORY_SEPARATOR . $r['filename'];

        echo "\nProcessing image ID {$r['id']} -> {$r['filename']}\n";

        if (!file_exists($originalPath)) {
            echo "  Original missing: {$originalPath}\n";
            continue;
        }

        // Ensure dirs exist
        foreach ([$thumbDir, $pristineDir] as $d) {
            if (!is_dir($d)) {
                if (!mkdir($d, 0775, true)) {
                    echo "  Failed to create directory: {$d}\n";
                    continue 2;
                }
            }
        }

        // Create pristine if missing
        if (!file_exists($pristinePath)) {
            if (copy($originalPath, $pristinePath)) {
                echo "  Created pristine: {$pristinePath}\n";
            } else {
                echo "  Failed to create pristine: {$pristinePath}\n";
            }
        } else {
            echo "  Pristine already exists\n";
        }

        // Create thumbnail if missing or zero-size
        $thumbOk = false;
        if (file_exists($thumbPath) && filesize($thumbPath) > 0) {
            echo "  Thumbnail already exists\n";
            $thumbOk = true;
        }

        if (!$thumbOk) {
            try {
                require_once __DIR__ . '/../app/Utils/ImageManipulator.php';
                $man = new ImageManipulator($originalPath, $config['image']['default_quality'] ?? 85);
                $thumbW = $config['image']['thumbnail_width'] ?? $config['image']['thumb_width'] ?? 200;
                $thumbH = $config['image']['thumbnail_height'] ?? $config['image']['thumb_height'] ?? 200;
                $man->thumbnail($thumbW, $thumbH);
                if ($man->save($thumbPath)) {
                    echo "  Thumbnail created: {$thumbPath} (" . filesize($thumbPath) . " bytes)\n";
                } else {
                    echo "  Thumbnail save failed for: {$thumbPath}\n";
                }
            } catch (Exception $e) {
                echo "  Error creating thumbnail: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\nDone.\n";

} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "\n";
    exit(1);
}
