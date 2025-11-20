#!/usr/bin/env powershell
# Integration test harness for image upload

$ErrorActionPreference = "Stop"

$BASE_URL = "http://localhost/imanage/public"
$TEST_USER = "integration_test_user"

Write-Host "Integration upload test starting against: $BASE_URL"
Write-Host "Using test user: $TEST_USER"

# Create/find test user
$userResult = php.exe "$PSScriptRoot\create_test_user.php" | ConvertFrom-Json
$userId = $userResult.user_id
Write-Host "Test user ID: $userId"

# Create session
$sessionId = php.exe "$PSScriptRoot\create_session.php" $userId $TEST_USER
Write-Host "Created session id: $sessionId"

# Generate sample image
$sampleImagePath = "$PSScriptRoot\sample.png"
php.exe "$PSScriptRoot\create_sample_image.php" $sampleImagePath | Out-Null
Write-Host "Sample image created at: $sampleImagePath"

# Upload the image
$apiUrl = "$BASE_URL/api.php?action=upload"
Write-Host "Uploading to: $apiUrl"
Write-Host "Running: curl.exe -v -b PHPSESSID=$sessionId -F image=@$sampleImagePath -F title=Integration Test -F description=`"Uploaded by integration test`" $apiUrl"

$response = curl.exe -v -b "PHPSESSID=$sessionId" -F "image=@$sampleImagePath" -F "title=Integration" -F "description=Uploaded" $apiUrl

Write-Host $response
Write-Host "Upload test complete. Check output above for JSON response and $PSScriptRoot\upload_error.txt for curl details."
