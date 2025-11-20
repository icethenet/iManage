<?php
require_once __DIR__ . '/../config/database.php';
try {
    $cfg = require __DIR__ . '/../config/database.php';
    $dsn = "mysql:host={$cfg['host']};dbname={$cfg['database']};charset={$cfg['charset']}";
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT id, user_id, filename, folder, original_path, thumb_path, pristine_path FROM images WHERE user_id = (SELECT id FROM users WHERE username = ?) LIMIT 10");
    $stmt->execute(['integration_test_user']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "No images found for integration_test_user\n";
        exit(0);
    }
    foreach ($rows as $r) {
        echo "ID: {$r['id']}\n";
        echo "Filename: {$r['filename']}\n";
        echo "Folder: {$r['folder']}\n";
        echo "Original Path: {$r['original_path']}\n";
        echo "Thumb Path: {$r['thumb_path']}\n";
        echo "Pristine Path: {$r['pristine_path']}\n";
        echo "---\n";
    }
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}
