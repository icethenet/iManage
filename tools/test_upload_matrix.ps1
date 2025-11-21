<#!
.SYNOPSIS
    Comprehensive upload test matrix (PowerShell) for iManage.
.DESCRIPTION
    Executes multiple upload scenarios (valid + error cases) and prints a summary.
    Scenarios:
      V1 valid JPEG
      V2 valid PNG
      V3 batch 5 sequential JPEGs
      V4 batch 5 parallel (Start-Job)
      E1 missing file field
      E2 unsupported mime (SVG)
      E3 oversized file >5MB
      E4 corrupted JPEG
      E5 wrong enctype (no multipart)
.PARAMETER Username
    Login username (default admin)
.PARAMETER Password
    Login password (default admin123)
.EXAMPLE
    ./test_upload_matrix.ps1
.EXAMPLE
    ./test_upload_matrix.ps1 -Username demo -Password secret
.NOTES
    Requires System.Drawing. Parallel jobs use Start-Job; gather results afterwards.
#>
param(
  [string]$Username='admin',
  [string]$Password='admin123'
)

$BaseUrl='http://localhost/imanage/public'
$ApiUrl="$BaseUrl/api.php?action=upload&__debug=1"
$CookiePath=Join-Path $env:TEMP ("imanage_matrix_cookie_"+[guid]::NewGuid()+'.txt')
$WorkDir=Join-Path $env:TEMP ("imanage_matrix_"+[guid]::NewGuid())
New-Item -ItemType Directory -Path $WorkDir | Out-Null

function Info($m){Write-Host "[INFO] $m" -ForegroundColor Cyan}
function Err($m){Write-Host "[ERROR] $m" -ForegroundColor Red}

# Login
Info "Logging in as $Username"
try { $loginResp = Invoke-WebRequest -Uri "$BaseUrl/api.php?action=login" -Method POST -Body "username=$Username&password=$Password" -ContentType 'application/x-www-form-urlencoded' -SessionVariable sess } catch { Err "Login HTTP error: $_"; exit 1 }
try { $loginJson = $loginResp.Content | ConvertFrom-Json } catch { Err 'Invalid login JSON'; exit 1 }
if(-not $loginJson.success){ Err "Login failed: $($loginJson.error)"; exit 1 }
Info 'Login OK'

Add-Type -AssemblyName System.Drawing -ErrorAction SilentlyContinue

function New-TestImage($Path,$W=320,$H=200){
  try { $bmp=new-object System.Drawing.Bitmap $W,$H; $g=[System.Drawing.Graphics]::FromImage($bmp); $bg=[System.Drawing.Color]::FromArgb(60,150,230); $g.Clear($bg); $white=[System.Drawing.Brushes]::White; $g.DrawString((Split-Path $Path -Leaf), (New-Object System.Drawing.Font 'Arial',12), $white, 10,10); $bmp.Save($Path,[System.Drawing.Imaging.ImageFormat]::Jpeg); $g.Dispose(); $bmp.Dispose(); return $true } catch { Err "Failed image gen: $_"; return $false }
}

function Upload-File($Path){
  $form=@{ image=Get-Item $Path; folder='default'; title='Matrix'; description='Matrix case'; tags='matrix' }
  try { $resp=Invoke-WebRequest -Uri $ApiUrl -Method POST -Form $form -WebSession $sess } catch { return @{ success=$false; error="HTTP $_" } }
  try { return ($resp.Content | ConvertFrom-Json) } catch { return @{ success=$false; error='Invalid JSON' } }
}

$Results=@{}
# V1
$v1=Join-Path $WorkDir 'valid1.jpg'; New-TestImage $v1 | Out-Null; $Results.V1=Upload-File $v1
# V2 PNG
$v2=Join-Path $WorkDir 'valid2.png'; $bmp=new-object System.Drawing.Bitmap 200,150; $g=[System.Drawing.Graphics]::FromImage($bmp); $g.Clear([System.Drawing.Color]::FromArgb(30,180,90)); $bmp.Save($v2,[System.Drawing.Imaging.ImageFormat]::Png); $g.Dispose(); $bmp.Dispose(); $Results.V2=Upload-File $v2
# V3 batch sequential
$seq=@(); for($i=0;$i -lt 5;$i++){ $p=Join-Path $WorkDir "seq_$i.jpg"; New-TestImage $p | Out-Null; $seq += Upload-File $p }
$Results.V3=$seq
# V4 parallel (jobs)
$jobs=@(); for($i=0;$i -lt 5;$i++){ $p=Join-Path $WorkDir "par_$i.jpg"; New-TestImage $p | Out-Null; $jobs += Start-Job -ScriptBlock { param($u,$sessObj,$path)
    $form=@{ image=Get-Item $path; folder='default'; title='Par'; description='Parallel'; tags='parallel' }
    $r=Invoke-WebRequest -Uri $u -Method POST -Form $form -WebSession $sessObj; $r.Content
  } -ArgumentList $ApiUrl,$sess,$p }
Wait-Job -Job $jobs | Out-Null
$par=@(); foreach($j in $jobs){ $c=Receive-Job $j; try { $par += ($c | ConvertFrom-Json) } catch { $par += @{ success=$false; error='Invalid JSON' } } Remove-Job $j }
$Results.V4=$par
# E1 missing file
try { $resp=Invoke-WebRequest -Uri $ApiUrl -Method POST -Form @{ folder='default' } -WebSession $sess } catch { $c=$_.Exception.Response.Content; } $Results.E1 = try { $resp.Content | ConvertFrom-Json } catch { @{ success=$false; error='Invalid JSON' } }
# E2 unsupported mime (SVG)
$svg=Join-Path $WorkDir 'bad.svg'; '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10" fill="red"/></svg>' | Set-Content $svg; $Results.E2=Upload-File $svg
# E3 oversized (>5MB)
$big=Join-Path $WorkDir 'big.jpg'; New-TestImage $big 3000 3000 | Out-Null; $Results.E3=Upload-File $big
# E4 corrupted JPEG
$cor=Join-Path $WorkDir 'corrupt.jpg'; Copy-Item $v1 $cor; $data=[IO.File]::ReadAllBytes($cor); $trunc=$data[0..([int]($data.Length/3))]; [IO.File]::WriteAllBytes($cor,$trunc); $Results.E4=Upload-File $cor
# E5 wrong enctype
try { $wrongResp=Invoke-WebRequest -Uri $ApiUrl -Method POST -Body 'folder=default' -ContentType 'application/x-www-form-urlencoded' -WebSession $sess } catch { $wrongResp=$null }
$Results.E5 = if($wrongResp){ try { $wrongResp.Content | ConvertFrom-Json } catch { @{ success=$false; error='Invalid JSON' } } } else { @{ success=$false; error='HTTP error' } }

# Summary
$Summary=@{}
foreach($k in $Results.Keys){ if($k -in 'V3','V4'){ $ok=($Results[$k] | Where-Object { $_.success }).Count; $Summary[$k]="${ok}/$($Results[$k].Count) succeeded" } else { $Summary[$k]= if($Results[$k].success){ 'OK' } else { 'FAIL: '+($Results[$k].error) } } }

Info '--- MATRIX SUMMARY ---'
$Summary.GetEnumerator() | ForEach-Object { Write-Host "$($_.Key) => $($_.Value)" }

$Output=[PSCustomObject]@{ summary=$Summary; results=$Results; workDir=$WorkDir }
$Output | ConvertTo-Json -Depth 6 | Write-Output
Info "Artifacts in: $WorkDir"
