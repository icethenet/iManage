<?php
/**
 * Test the 2FA API endpoint
 */

// Start session (simulating logged-in user)
session_start();

// Set a fake user_id (use your actual user ID)
$_SESSION['user_id'] = 15; // John

// Simulate the API call
$_GET['action'] = 'get_2fa_status';

// Capture output
ob_start();

try {
    require __DIR__ . '/../public/api.php';
    $output = ob_get_clean();
    
    echo "API Response:\n";
    echo $output . "\n\n";
    
    $json = json_decode($output, true);
    if ($json) {
        echo "Parsed JSON:\n";
        print_r($json);
    } else {
        echo "❌ Not valid JSON\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

