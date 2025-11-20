<?php
$cfg = require __DIR__ . '/../config/database.php';
try {
    $pdo = new PDO('mysql:host='.$cfg['host'].';dbname='.$cfg['database'].';charset='.$cfg['charset'], $cfg['username'], $cfg['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT id, filename, folder FROM images WHERE user_id = (SELECT id FROM users WHERE username = ?)');
    $stmt->execute(['integration_test_user']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { echo "No images for integration_test_user\n"; exit; }
    foreach ($rows as $r) {
        echo "ID: {$r['id']}\nFilename: {$r['filename']}\nFolder: {$r['folder']}\n---\n";
    }
} catch (Exception $e) { echo 'DB error: ' . $e->getMessage() . "\n"; }
