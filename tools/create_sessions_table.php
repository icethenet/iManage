<?php
$config = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
    $config['username'],
    $config['password']
);
$sql = file_get_contents(__DIR__ . '/../database/migrations/add_sessions_table.sql');
$pdo->exec($sql);
echo "Sessions table created successfully\n";
