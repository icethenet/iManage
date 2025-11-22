<?php
/**
 * Verify GrapesJS Landing Pages Integration
 * Checks database table, files, and configuration
 */

$projectRoot = dirname(__DIR__);
$errors = [];
$warnings = [];
$success = [];

echo "=== GrapesJS Landing Pages - Verification ===\n\n";

// 1. Check Database Table
echo "1. Checking Database Table...\n";
try {
    require_once $projectRoot . '/app/Database.php';
    $db = Database::getInstance()->getConnection();
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'landing_pages'");
    if ($stmt->fetch()) {
        $success[] = "✅ landing_pages table exists";
        
        // Check columns
        $requiredColumns = ['id', 'user_id', 'share_token', 'page_title', 'html_content', 'css_content', 'grapesjs_data', 'is_active'];
        $stmt = $db->query("DESCRIBE landing_pages");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        foreach ($requiredColumns as $col) {
            if (in_array($col, $columns)) {
                $success[] = "✅ Column exists: $col";
            } else {
                $errors[] = "❌ Missing column: $col";
            }
        }
    } else {
        $errors[] = "❌ landing_pages table not found";
        $warnings[] = "⚠️  Run: php tools/create_landing_pages_table.php";
    }
} catch (Exception $e) {
    $errors[] = "❌ Database error: " . $e->getMessage();
    $warnings[] = "⚠️  Ensure MySQL is running";
}

// 2. Check Required Files
echo "\n2. Checking Required Files...\n";
$requiredFiles = [
    'public/design-landing.php' => 'GrapesJS editor page',
    'public/js/grapesjs-manager.js' => 'GrapesJS manager class',
    'tools/create_landing_pages_table.php' => 'Migration script',
    'database/migrations/add_landing_pages_table.sql' => 'SQL schema',
    'docs/CUSTOM_LANDING_PAGES.md' => 'Feature documentation',
    'docs/GRAPES_JS_IMPLEMENTATION.md' => 'Implementation summary'
];

foreach ($requiredFiles as $file => $description) {
    $path = $projectRoot . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $success[] = "✅ $description ($size bytes)";
    } else {
        $errors[] = "❌ Missing: $file ($description)";
    }
}

// 3. Check API Endpoints
echo "\n3. Checking API Endpoints...\n";
$apiFile = $projectRoot . '/public/api.php';
$apiContent = file_get_contents($apiFile);

$requiredEndpoints = [
    'saveLandingPage' => 'case \'saveLandingPage\':',
    'loadLandingPage' => 'case \'loadLandingPage\':',
    'requireLogin helper' => 'function requireLogin()'
];

foreach ($requiredEndpoints as $name => $pattern) {
    if (strpos($apiContent, $pattern) !== false) {
        $success[] = "✅ API endpoint: $name";
    } else {
        $errors[] = "❌ Missing API endpoint: $name";
    }
}

// 4. Check Share Page Modifications
echo "\n4. Checking Share Page...\n";
$shareFile = $projectRoot . '/public/share.php';
$shareContent = file_get_contents($shareFile);

$shareChecks = [
    'Custom landing container' => 'custom-landing-container',
    'Design mode button' => 'design-mode-btn',
    'checkCustomLanding function' => 'checkCustomLanding',
    'displayCustomLanding function' => 'displayCustomLanding',
    'checkDesignPermission function' => 'checkDesignPermission'
];

foreach ($shareChecks as $name => $pattern) {
    if (strpos($shareContent, $pattern) !== false) {
        $success[] = "✅ Share page: $name";
    } else {
        $errors[] = "❌ Share page missing: $name";
    }
}

// 5. Check README Updates
echo "\n5. Checking Documentation...\n";
$readmeFile = $projectRoot . '/README.md';
$readmeContent = file_get_contents($readmeFile);

if (strpos($readmeContent, 'Custom Landing Pages') !== false) {
    $success[] = "✅ README mentions Custom Landing Pages";
} else {
    $warnings[] = "⚠️  README should mention Custom Landing Pages feature";
}

if (strpos($readmeContent, 'CUSTOM_LANDING_PAGES.md') !== false) {
    $success[] = "✅ README links to documentation";
} else {
    $warnings[] = "⚠️  README should link to CUSTOM_LANDING_PAGES.md";
}

// 6. File Permissions Check (Windows)
echo "\n6. Checking File Permissions...\n";
$writableDirs = ['logs', 'public/uploads'];
foreach ($writableDirs as $dir) {
    $path = $projectRoot . '/' . $dir;
    if (is_writable($path)) {
        $success[] = "✅ Writable: $dir";
    } else {
        $warnings[] = "⚠️  May need write permissions: $dir";
    }
}

// 7. Print Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "VERIFICATION SUMMARY\n";
echo str_repeat("=", 50) . "\n\n";

if (count($success) > 0) {
    echo "✅ SUCCESS (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "❌ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

// Final Status
echo str_repeat("=", 50) . "\n";
if (count($errors) === 0) {
    echo "✅ VERIFICATION PASSED - Ready to use!\n";
    echo "\nNext Steps:\n";
    echo "1. Start web server (if not running)\n";
    echo "2. Login to iManage\n";
    echo "3. Share an image\n";
    echo "4. Click '🎨 Design Landing Page'\n";
    echo "5. Build your custom page!\n";
} else {
    echo "❌ VERIFICATION FAILED - Fix errors above\n";
    echo "\nQuick Fix:\n";
    echo "php tools/create_landing_pages_table.php\n";
}
echo str_repeat("=", 50) . "\n";
