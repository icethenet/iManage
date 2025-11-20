<?php
require_once __DIR__ . '/../app/Database.php';

$db = Database::getInstance();

echo "Current Database Schema\n";
echo "=======================\n\n";

// Get all tables
$tables = ['users', 'images', 'folders'];

foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW CREATE TABLE $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo "-- Table: $table\n";
            echo $result['Create Table'] . ";\n\n";
        }
    } catch (Exception $e) {
        echo "-- Table $table: " . $e->getMessage() . "\n\n";
    }
}
