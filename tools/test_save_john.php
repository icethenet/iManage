<?php
require __DIR__ . '/../app/Utils/ImageManipulator.php';
$src = __DIR__ . '/../public/uploads/John/Asian Girls/original/01_691d3e4db2197.jpg';
if (!file_exists($src)) {
    echo "MISSING_SRC\n";
    exit(2);
}
try {
    $m = new ImageManipulator($src);
    $dst = str_replace('.jpg', '._test_save.jpg', $src);
    $ok = $m->save($dst);
    echo $ok ? "SAVED\n" : "SAVE_FAILED\n";
    if (!$ok) {
        $err = error_get_last();
        if ($err) echo "ERR: " . ($err['message'] ?? 'unknown') . "\n";
    } else {
        echo "Saved to: $dst\n";
        echo "Size: " . (file_exists($dst) ? filesize($dst) : 'missing') . "\n";
    }
} catch (Exception $e) {
    echo "EXC: " . $e->getMessage() . "\n";
    exit(1);
}
