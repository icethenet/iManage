<?php
$config = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
    $config['username'],
    $config['password']
);
$sql = file_get_contents(__DIR__ . '/../database/migrations/add_ip_access_control_table.sql');
$pdo->exec($sql);
echo "IP access control table created successfully\n";
