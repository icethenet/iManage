<#!
.SYNOPSIS
    Automated upload endpoint test for iManage (PowerShell).
.DESCRIPTION
    Performs:
      1. Login (stores cookies in temp file)
      2. Generates a temporary JPEG test image (using .NET System.Drawing)
      3. Multipart/form-data POST to upload endpoint with __debug=1
      4. Parses JSON response, reports success/failure
      5. Displays tail of logs/upload_debug.log if present
.PARAMETER Username
    Login username (default admin).
.PARAMETER Password
    Login password (default admin123).
.EXAMPLE
    ./test_upload_endpoint.ps1
.EXAMPLE
    ./test_upload_endpoint.ps1 -Username demo -Password secret
.NOTES
    Requires .NET (Windows PowerShell) and System.Drawing.Common available.
    If System.Drawing cannot create image, will fallback to a base64 embedded JPEG.
#>
param(
    [string]$Username = 'admin',
    [string]$Password = 'admin123'
)

$ErrorActionPreference = 'Stop'
$BaseUrl = 'http://localhost/imanage/public'
$ApiUrl  = "$BaseUrl/api.php"

function Write-Info($m){ Write-Host "[INFO] $m" -ForegroundColor Cyan }
function Write-Err($m){ Write-Host "[ERROR] $m" -ForegroundColor Red }

# Temp paths
$CookiePath = Join-Path $env:TEMP ("imanage_cookie_" + [guid]::NewGuid().ToString() + '.txt')
$ImagePath  = Join-Path $env:TEMP ("imanage_test_" + [guid]::NewGuid().ToString() + '.jpg')

# 1. Login
Write-Info "Logging in as '$Username'"
$loginBody = "username=$Username&password=$Password"
try {
    $loginResponse = Invoke-WebRequest -Uri "$ApiUrl?action=login" -Method POST -Body $loginBody -ContentType 'application/x-www-form-urlencoded' -SessionVariable sess -ErrorAction Stop
} catch {
    Write-Err "Login HTTP error: $_"; exit 1
}
try { $loginJson = $loginResponse.Content | ConvertFrom-Json } catch { Write-Err "Invalid JSON from login"; exit 1 }
if (-not $loginJson.success) { Write-Err "Login failed: $($loginJson.error)"; exit 1 }
Write-Info "Login successful."

# 2. Generate test image
Write-Info "Generating test image at $ImagePath"
$imgGenerated = $false
try {
    Add-Type -AssemblyName System.Drawing
    $bmp = New-Object System.Drawing.Bitmap 320,200
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $bg = [System.Drawing.Color]::FromArgb(30,136,229)
    $g.Clear($bg)
    $font = New-Object System.Drawing.Font 'Arial',12
    $brush = [System.Drawing.Brushes]::White
    $g.DrawString('iManage Upload Test', $font, $brush, 10, 30)
    $g.DrawString((Get-Date).ToString('yyyy-MM-dd HH:mm:ss'), $font, $brush, 10, 60)
    $bmp.Save($ImagePath, [System.Drawing.Imaging.ImageFormat]::Jpeg)
    $g.Dispose(); $bmp.Dispose()
    $imgGenerated = Test-Path $ImagePath
} catch {
    Write-Err "System.Drawing generation failed, will fallback to embedded JPEG: $_"
}
if (-not $imgGenerated) {
    Write-Info "Writing fallback embedded JPEG"
    $fallbackBase64 = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAEBAQEBAQEBAQEBAQECAgICAgQDAgICAQECAgQDAgIDBQMDBAQDBQYEBQYGBQUHBwcGBgYGBwkICQsJCgwO......' # truncated placeholder
    [IO.File]::WriteAllBytes($ImagePath, [Convert]::FromBase64String($fallbackBase64))
}
$size = (Get-Item $ImagePath).Length
Write-Info "Image ready (size: $size bytes)"

# 3. Multipart upload
Write-Info "Uploading image..."
try {
    $form = @{
        image      = Get-Item $ImagePath
        folder     = 'default'
        title      = 'AutomatedTestPS'
        description= 'Automated upload test (PowerShell)'
        tags       = 'test,automated,powershell'
    }
    $uploadResponse = Invoke-WebRequest -Uri "$ApiUrl?action=upload&__debug=1" -Method Post -Form $form -WebSession $sess -ErrorAction Stop
} catch {
    Write-Err "Upload HTTP error: $_"; Remove-Item $ImagePath -Force; exit 2
}
try { $uploadJson = $uploadResponse.Content | ConvertFrom-Json } catch { Write-Err "Invalid JSON upload response"; Remove-Item $ImagePath -Force; exit 2 }

if ($uploadJson.success) {
    Write-Info "Upload succeeded. Filename=$($uploadJson.data.filename) ID=$($uploadJson.data.id)"
    if ($uploadJson.debug.log_file) { Write-Info "Debug log: $($uploadJson.debug.log_file)" }
    $exitCode = 0
} else {
    Write-Err "Upload failed: $($uploadJson.error)"
    $exitCode = 2
}

# 4. Tail debug log
$debugLog = Join-Path (Split-Path $PSScriptRoot -Parent) 'logs/upload_debug.log'
if (Test-Path $debugLog) {
    Write-Info '--- Tail of upload_debug.log ---'
    Get-Content $debugLog -Tail 20 | ForEach-Object { $_ }
    Write-Info '--- End log tail ---'
}

# 5. Cleanup
Remove-Item $ImagePath -Force -ErrorAction SilentlyContinue
exit $exitCode
