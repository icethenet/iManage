<?php
$config = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
    $config['username'],
    $config['password']
);
$sql = file_get_contents(__DIR__ . '/../database/migrations/add_failed_logins_table.sql');
$pdo->exec($sql);
echo "Failed logins table created successfully\n";
