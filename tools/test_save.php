<?php
require __DIR__ . '/../app/Utils/ImageManipulator.php';
$src = __DIR__ . '/../public/uploads/integration_test_user/original/sample_upload2_691d40a953027.png';
if (!file_exists($src)) {
    echo "MISSING_SRC\n";
    exit(2);
}
try {
    $m = new ImageManipulator($src);
    $dst = str_replace('.png', '._test_save.png', $src);
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
