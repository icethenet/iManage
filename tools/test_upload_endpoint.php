<?php
/**
 * Automated Upload Endpoint Test
 *
 * Steps:
 * 1. Log in using provided credentials (default admin/admin123).
 * 2. Generate a temporary JPEG image using GD.
 * 3. POST multipart/form-data to upload endpoint with __debug=1.
 * 4. Parse JSON response and report success/failure.
 * 5. Output tail of debug log if available.
 *
 * Usage:
 *   php tools/test_upload_endpoint.php [username] [password]
 * Example:
 *   php tools/test_upload_endpoint.php admin admin123
 *
 * Exit codes:
 *   0 = success
 *   1 = login failed
 *   2 = upload failed
 *   3 = environment (GD / curl) missing
 */

$baseUrl = 'http://localhost/imanage/public'; // Adjust if served elsewhere
$apiUrl  = $baseUrl . '/api.php';
$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'admin123';

if (!function_exists('curl_init')) {
    fwrite(STDERR, "cURL extension required.\n");
    exit(3);
}
if (!function_exists('imagecreatetruecolor')) {
    fwrite(STDERR, "GD extension required to generate test image.\n");
    exit(3);
}

$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imanage_test_cookie_' . uniqid() . '.txt';
$testImage  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imanage_test_image_' . uniqid() . '.jpg';

function logInfo($msg) { echo "[INFO] $msg\n"; }
function logError($msg) { fwrite(STDERR, "[ERROR] $msg\n"); }

// 1. Login
logInfo("Logging in as '$username'.");
$loginCurl = curl_init();
curl_setopt_array($loginCurl, [
    CURLOPT_URL => $apiUrl . '?action=login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['username' => $username, 'password' => $password]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
]);
$loginResponse = curl_exec($loginCurl);
$loginErr = curl_error($loginCurl);
curl_close($loginCurl);
if ($loginErr) {
    logError('cURL error during login: ' . $loginErr);
    exit(1);
}
$loginJson = json_decode($loginResponse, true);
if (!$loginJson || empty($loginJson['success'])) {
    logError('Login failed. Response: ' . $loginResponse);
    exit(1);
}
logInfo('Login successful.');

// 2. Generate temporary JPEG
logInfo('Generating test image: ' . $testImage);
$w = 320; $h = 200;
$im = imagecreatetruecolor($w, $h);
$bg = imagecolorallocate($im, 30, 136, 229); // blue-ish
$fg = imagecolorallocate($im, 255, 255, 255);
imagefilledrectangle($im, 0, 0, $w, $h, $bg);
$text = 'iManage Upload Test';
$text2 = date('Y-m-d H:i:s');
imagettftext($im, 12, 0, 12, 30, $fg, __DIR__ . DIRECTORY_SEPARATOR . 'arial.ttf', $text);
imagettftext($im, 10, 0, 12, 52, $fg, __DIR__ . DIRECTORY_SEPARATOR . 'arial.ttf', $text2);
// If TTF font not available, fallback to imagestring
if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'arial.ttf')) {
    imagestring($im, 4, 12, 30, $text, $fg);
    imagestring($im, 3, 12, 52, $text2, $fg);
}
imagejpeg($im, $testImage, 85);
imagedestroy($im);
if (!file_exists($testImage)) {
    logError('Failed to create test image file.');
    exit(3);
}
logInfo('Image created (size ' . filesize($testImage) . ' bytes).');

// 3. Upload via multipart/form-data
logInfo('Uploading image...');
$uploadCurl = curl_init();
$postFields = [
    'image' => new CURLFile($testImage, 'image/jpeg', basename($testImage)),
    'folder' => 'default',
    'title' => 'AutomatedTest',
    'description' => 'Automated upload test run',
    'tags' => 'test,automated',
];
curl_setopt_array($uploadCurl, [
    CURLOPT_URL => $apiUrl . '?action=upload&__debug=1',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
]);
$uploadResponse = curl_exec($uploadCurl);
$uploadErr = curl_error($uploadCurl);
curl_close($uploadCurl);

if ($uploadErr) {
    logError('cURL error during upload: ' . $uploadErr);
    @unlink($testImage);
    exit(2);
}

$uploadJson = json_decode($uploadResponse, true);
if (!$uploadJson) {
    logError('Invalid JSON upload response: ' . $uploadResponse);
    @unlink($testImage);
    exit(2);
}

if (!empty($uploadJson['success'])) {
    logInfo('Upload succeeded. ID=' . ($uploadJson['data']['id'] ?? 'unknown') . ' Filename=' . ($uploadJson['data']['filename'] ?? 'n/a'));
    if (!empty($uploadJson['debug']['log_file'])) {
        logInfo('Debug log file reported: ' . $uploadJson['debug']['log_file']);
    }
    $exitCode = 0;
} else {
    logError('Upload failed: ' . ($uploadJson['error'] ?? 'Unknown error') . ' Raw response: ' . $uploadResponse);
    $exitCode = 2;
}

// 4. Tail debug log if exists
$debugLog = __DIR__ . '/../logs/upload_debug.log';
if (file_exists($debugLog)) {
    logInfo('--- Tail of upload_debug.log ---');
    $lines = @file($debugLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $tail = array_slice($lines, -20);
    foreach ($tail as $line) {
        echo $line . "\n";
    }
    logInfo('--- End log tail ---');
}

// 5. Cleanup temp image
@unlink($testImage);
@unlink($cookieFile);
exit($exitCode);
