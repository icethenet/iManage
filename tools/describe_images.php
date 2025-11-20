<?php
$cfg = require __DIR__ . '/../config/database.php';
try {
    $pdo = new PDO('mysql:host='.$cfg['host'].';dbname='.$cfg['database'].';charset='.$cfg['charset'], $cfg['username'], $cfg['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query('DESCRIBE images');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo $r['Field'] . "\n";
    }
} catch (Exception $e) {
    echo 'DB error: ' . $e->getMessage() . "\n";
}
