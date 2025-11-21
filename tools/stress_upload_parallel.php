<?php
/**
 * stress_upload_parallel.php
 * High-concurrency upload stress & size limit verification.
 *
 * Features:
 *  - Logs in (session reuse)
 *  - Generates N random JPEG images (default 40) in temp dir
 *  - Parallel (curl_multi) uploads in batches to reduce memory spikes
 *  - Measures per-request duration & aggregate throughput
 *  - Creates size category test images: nearLow (~4MB), nearHigh (~just under limit), overLimit (> limit)
 *  - Confirms backend size rejection (HTTP JSON error) for oversize
 *  - Outputs JSON summary + human-readable report
 *
 * Usage:
 *   php tools/stress_upload_parallel.php [username] [password] [count] [batch]
 * Example:
 *   php tools/stress_upload_parallel.php admin admin123 60 12
 *
 * Exit codes:
 *   0 success
 *   1 login failure
 *   2 environment missing (cURL/GD)
 *   3 partial failures (some uploads failed unexpectedly)
 */

$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'admin123';
$total    = (int)($argv[3] ?? 40); // total random images
$batchSz  = (int)($argv[4] ?? 10); // parallel batch size
if ($total < 1) $total = 1;
if ($batchSz < 1) $batchSz = 5;

function out($m){ echo "[INFO] $m\n"; }
function err($m){ fwrite(STDERR, "[ERROR] $m\n"); }

if (!function_exists('curl_init') || !function_exists('curl_multi_init')) { err('cURL multi required'); exit(2); }
if (!function_exists('imagecreatetruecolor')) { err('GD extension required'); exit(2); }

// Load config to get size limit
$config = require dirname(__DIR__) . '/config/app.php';
$limitBytes = $config['image']['max_file_size'] ?? ($config['image']['max_size'] ?? 0);
out('Configured max file size: ' . number_format($limitBytes/1024/1024,2) . ' MB');

$baseUrl = 'http://localhost/imanage/public';
$apiBase = $baseUrl . '/api.php';
$uploadUrl = $apiBase . '?action=upload&__debug=1';

$cookieFile = sys_get_temp_dir() . '/imanage_stress_cookie_' . uniqid() . '.txt';

// Login
$loginCh = curl_init();
curl_setopt_array($loginCh, [
    CURLOPT_URL => $apiBase . '?action=login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['username'=>$username,'password'=>$password]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile
]);
$loginResp = curl_exec($loginCh); $loginErr = curl_error($loginCh); curl_close($loginCh);
if ($loginErr) { err('Login cURL error: '.$loginErr); exit(1); }
$loginJson = json_decode($loginResp, true);
if (!$loginJson || empty($loginJson['success'])) { err('Login failed: '.$loginResp); exit(1); }
out('Login OK');

// Workspace
$workDir = sys_get_temp_dir() . '/imanage_stress_' . uniqid();
@mkdir($workDir) || err('Could not create workDir: '.$workDir);
out('Work dir: '.$workDir);

// Helper to create a JPEG of roughly target size by adjusting dimensions
function makeApproxSizeJpeg(string $path, int $targetBytes): int {
    // Very rough: pick dimension so pixels ~ targetBytes/3 (JPEG compression ~3 bytes/pixel for colored blank at quality 85)
    $pixels = max(1000, (int)($targetBytes / 3));
    $side = (int)sqrt($pixels);
    $w = $side; $h = $side;
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, rand(0,255), rand(0,255), rand(0,255));
    imagefilledrectangle($im,0,0,$w,$h,$bg);
    $white = imagecolorallocate($im,255,255,255);
    imagestring($im, 5, 10, 10, 'SIZE TEST', $white);
    imagejpeg($im, $path, 85);
    imagedestroy($im);
    return filesize($path);
}

// Create size category images
$nearLowTarget = (int)($limitBytes * 0.80); // ~80% limit
$nearHighTarget = (int)($limitBytes * 0.97); // ~just under limit
$overLimitTarget = (int)($limitBytes * 1.10); // exceed limit

$nearLow = $workDir . '/nearLow.jpg';
$nearHigh = $workDir . '/nearHigh.jpg';
$overLimit = $workDir . '/overLimit.jpg';
makeApproxSizeJpeg($nearLow, $nearLowTarget);
makeApproxSizeJpeg($nearHigh, $nearHighTarget);
makeApproxSizeJpeg($overLimit, $overLimitTarget);

out('nearLow size: '.filesize($nearLow).' bytes');
out('nearHigh size: '.filesize($nearHigh).' bytes');
out('overLimit size: '.filesize($overLimit).' bytes');

// Generate random test images (smaller < limit)
function makeRandomJpeg(string $path) {
    $w = rand(400, 800);
    $h = rand(300, 600);
    $im = imagecreatetruecolor($w,$h);
    $bg = imagecolorallocate($im, rand(0,255), rand(0,255), rand(0,255));
    imagefilledrectangle($im,0,0,$w,$h,$bg);
    $c = imagecolorallocate($im,255,255,255);
    imagestring($im, 3, 5, 5, 'Stress '.basename($path), $c);
    imagejpeg($im, $path, 85);
    imagedestroy($im);
}

$imagePaths = [];
for ($i=0; $i < $total; $i++) {
    $p = $workDir . '/img_' . $i . '.jpg';
    makeRandomJpeg($p);
    $imagePaths[] = $p;
}

// Upload helper returns [success, error?, ms]
function buildCurlHandle(string $url, string $filePath, string $cookieFile) {
    $ch = curl_init();
    $fields = [
        'image' => new CURLFile($filePath, mime_content_type($filePath) ?: 'image/jpeg', basename($filePath)),
        'folder' => 'default',
        'title' => 'Stress',
        'description' => 'Stress parallel upload',
        'tags' => 'stress'
    ];
    curl_setopt_array($ch,[
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
    ]);
    return $ch;
}

function execBatch(array $paths, string $url, string $cookieFile): array {
    $mh = curl_multi_init();
    $map = [];
    foreach ($paths as $p) {
        $h = buildCurlHandle($url, $p, $cookieFile);
        $map[(int)$h] = ['handle'=>$h,'path'=>$p,'start'=>microtime(true)];
        curl_multi_add_handle($mh,$h);
    }
    do { $status = curl_multi_exec($mh,$running); if($running) curl_multi_select($mh,1.0); } while($running > 0);
    $results=[];
    foreach ($map as $k=>$meta) {
        $h = $meta['handle'];
        $raw = curl_multi_getcontent($h);
        $err = curl_error($h);
        $ms = (microtime(true) - $meta['start']) * 1000;
        $json = $raw ? json_decode($raw,true) : null;
        $ok = $json && !empty($json['success']);
        $results[] = [
            'file' => basename($meta['path']),
            'bytes' => filesize($meta['path']),
            'success' => $ok,
            'error' => $ok ? null : ($err ?: ($json['error'] ?? 'invalid json')), 
            'ms' => round($ms,1)
        ];
        curl_multi_remove_handle($mh,$h);
        curl_close($h);
    }
    curl_multi_close($mh);
    return $results;
}

$allResults = [];
$startAll = microtime(true);
for ($offset=0; $offset < count($imagePaths); $offset += $batchSz) {
    $slice = array_slice($imagePaths, $offset, $batchSz);
    $batchRes = execBatch($slice, $uploadUrl, $cookieFile);
    $allResults = array_merge($allResults, $batchRes);
    out('Batch '.(int)($offset/$batchSz+1).' uploaded.');
}
$durationAll = microtime(true) - $startAll;

// Size category tests (sequential for clarity)
function uploadSingle($file,$url,$cookieFile){
    $ch = buildCurlHandle($url,$file,$cookieFile);
    $start = microtime(true);
    $raw = curl_exec($ch); $err = curl_error($ch); $ms = (microtime(true)-$start)*1000; curl_close($ch);
    $json = $raw ? json_decode($raw,true) : null;
    $ok = $json && !empty($json['success']);
    return [ 'file'=>basename($file),'bytes'=>filesize($file),'success'=>$ok,'error'=>$ok?null:($err ?: ($json['error'] ?? 'invalid json')),'ms'=>round($ms,1) ];
}

$sizeTests = [
    'nearLow' => uploadSingle($nearLow,$uploadUrl,$cookieFile),
    'nearHigh' => uploadSingle($nearHigh,$uploadUrl,$cookieFile),
    'overLimit' => uploadSingle($overLimit,$uploadUrl,$cookieFile),
];

// Aggregate stats
$successes = array_filter($allResults, fn($r)=>$r['success']);
$fails = array_filter($allResults, fn($r)=>!$r['success']);
$avgMs = count($allResults) ? array_sum(array_column($allResults,'ms'))/count($allResults) : 0;
$throughput = $durationAll>0 ? (count($allResults)/$durationAll) : 0; // uploads per second

out('Parallel uploads total: '.count($allResults).' in '.round($durationAll,2).'s');
out('Average latency: '.round($avgMs,1).' ms');
out('Throughput: '.round($throughput,2).' uploads/sec');
out('Success: '.count($successes).', Fail: '.count($fails));

// Validate size limits logic: nearHigh should succeed, overLimit should fail (based on config)
$sizeLimitChecks = [
    'nearHigh_expected' => 'success',
    'overLimit_expected' => 'fail',
    'nearHigh_actual' => $sizeTests['nearHigh']['success'] ? 'success':'fail',
    'overLimit_actual' => $sizeTests['overLimit']['success'] ? 'success':'fail',
];

$summary = [
    'total_parallel' => count($allResults),
    'parallel_success' => count($successes),
    'parallel_fail' => count($fails),
    'avg_ms' => round($avgMs,1),
    'throughput_uploads_per_sec' => round($throughput,2),
    'size_tests' => $sizeTests,
    'size_limit_checks' => $sizeLimitChecks,
    'limit_bytes' => $limitBytes,
    'work_dir' => $workDir
];

echo json_encode(['summary'=>$summary,'parallel'=>$allResults], JSON_PRETTY_PRINT) . "\n";

// Exit with 3 if unexpected size behavior or >5% failures
$unexpected = ($sizeLimitChecks['nearHigh_actual'] !== 'success') || ($sizeLimitChecks['overLimit_actual'] !== 'fail');
$failRate = count($allResults) ? (count($fails)/count($allResults)) : 0;
if ($unexpected || $failRate > 0.05) {
    err('Stress test revealed issues (unexpected size acceptance/rejection or high failure rate).');
    exit(3);
}
exit(0);
?>