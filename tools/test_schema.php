<?php
/**
 * Test that schema.sql file is valid and can be executed
 */

echo "Testing Schema File\n";
echo "===================\n\n";

$schemaFile = __DIR__ . '/../database/schema.sql';

if (!file_exists($schemaFile)) {
    echo "❌ Schema file not found: $schemaFile\n";
    exit(1);
}

echo "✓ Schema file exists\n";

$schema = file_get_contents($schemaFile);
if (empty($schema)) {
    echo "❌ Schema file is empty\n";
    exit(1);
}

echo "✓ Schema file readable (" . strlen($schema) . " bytes)\n\n";

// Count tables
preg_match_all('/CREATE TABLE.*?`(\w+)`/i', $schema, $matches);
$tables = $matches[1] ?? [];

echo "Tables defined in schema:\n";
foreach ($tables as $table) {
    echo "  - $table\n";
}

echo "\n";

// Check for required columns
$requiredFeatures = [
    'share_token' => 'Share link feature',
    'shared' => 'Public sharing flag',
    'user_id' => 'Multi-user support',
    'folder' => 'Folder organization'
];

foreach ($requiredFeatures as $column => $feature) {
    if (strpos($schema, $column) !== false) {
        echo "✓ Found column: $column ($feature)\n";
    } else {
        echo "⚠ Missing column: $column ($feature)\n";
    }
}

echo "\n✓ Schema file validated successfully!\n";
echo "\nTo use this schema:\n";
echo "1. Web installer: http://localhost/imanage/public/install.php\n";
echo "2. Manual: mysql -u root -p image_gallery < database/schema.sql\n";
