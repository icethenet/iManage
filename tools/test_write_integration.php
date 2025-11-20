<?php
$dir = __DIR__ . '/../public/uploads/integration_test_user/original';
$dst = $dir . '/__test_write_' . time() . '.txt';
try {
    $ok = file_put_contents($dst, "test\n");
    if ($ok === false) {
        echo "WRITE_FAILED\n";
        exit(1);
    }
    echo "WRITE_OK: $dst\n";
    echo "Size: " . filesize($dst) . "\n";
} catch (Exception $e) {
    echo "EXC: " . $e->getMessage() . "\n";
    exit(1);
}
