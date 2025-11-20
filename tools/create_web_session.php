<?php
if ($argc < 2) { fwrite(STDERR, "Usage: php create_web_session.php <username>\n"); exit(2); }
$username = $argv[1];
$cfg = require __DIR__ . '/../config/database.php';
try {
    $pdo = new PDO('mysql:host='.$cfg['host'].';dbname='.$cfg['database'].';charset='.$cfg['charset'], $cfg['username'], $cfg['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { fwrite(STDERR, "User not found\n"); exit(3); }
    $userId = $row['id'];
    // ensure session save path matches Apache's (uses same PHP config in many setups)
    session_save_path('C:/www/tmp');
    session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    session_write_close();
    echo session_id() . PHP_EOL;
} catch (Exception $e) { echo 'ERR: ' . $e->getMessage() . "\n"; exit(4); }
