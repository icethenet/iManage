<?php
/**
 * test_upload_matrix.php
 * Comprehensive upload test matrix for iManage.
 *
 * Scenarios:
 *  V1  small valid JPEG
 *  V2  small valid PNG
 *  V3  batch 5 JPEGs sequential
 *  V4  batch 5 JPEGs parallel (curl_multi)
 *  E1  missing file field
 *  E2  unsupported mime (SVG)
 *  E3  oversized file (>5MB)
 *  E4  corrupted JPEG (truncated)
 *  E5  wrong enctype (no multipart)
 *
 * Usage:
 *   php tools/test_upload_matrix.php [username] [password]
 *
 * Output: JSON summary to STDOUT + human-readable lines.
 */

$baseUrl = 'http://localhost/imanage/public';
$apiUrl  = $baseUrl . '/api.php?action=upload&__debug=1';
$user = $argv[1] ?? 'admin';
$pass = $argv[2] ?? 'admin123';

function fail($m,$code=1){fwrite(STDERR,"[FATAL] $m\n");exit($code);} 
function info($m){echo "[INFO] $m\n";} 

if(!function_exists('curl_init')) fail('cURL extension required');
if(!function_exists('imagecreatetruecolor')) fail('GD extension required');

$cookieFile = sys_get_temp_dir().'/imanage_matrix_cookie_'.uniqid().'.txt';

// Login
$login = curl_init();
curl_setopt_array($login,[CURLOPT_URL=>str_replace('upload&','login&', $apiUrl),CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['username'=>$user,'password'=>$pass]),CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cookieFile,CURLOPT_COOKIEFILE=>$cookieFile]);
$loginResp = curl_exec($login);curl_close($login);
$loginJson = json_decode($loginResp,true); if(!$loginJson||empty($loginJson['success'])) fail('Login failed: '.$loginResp,2);
info('Login OK');

$results=[];
$workDir = sys_get_temp_dir().'/imanage_matrix_'.uniqid();
@mkdir($workDir);

function makeImage($path,$w=400,$h=300,$bg=[60,150,230]){ $im=imagecreatetruecolor($w,$h); $c=imagecolorallocate($im,$bg[0],$bg[1],$bg[2]); imagefilledrectangle($im,0,0,$w,$h,$c); $white=imagecolorallocate($im,255,255,255); imagestring($im,5,10,10,'TEST '.basename($path),$white); imagejpeg($im,$path,85); imagedestroy($im);} 

function uploadFile($filePath,$cookieFile,$extra=[]){ global $apiUrl; $ch=curl_init(); $fields=array_merge([
 'image'=>new CURLFile($filePath,mime_content_type($filePath)?:'image/jpeg',basename($filePath)),
 'folder'=>'default','title'=>'Matrix','description'=>'Matrix case','tags'=>'matrix'
 ],$extra);
 curl_setopt_array($ch,[CURLOPT_URL=>$apiUrl,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$fields,CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cookieFile,CURLOPT_COOKIEFILE=>$cookieFile]);
 $resp=curl_exec($ch); $err=curl_error($ch); curl_close($ch); if($err) return ['success'=>false,'error'=>'curl:'.$err,'raw'=>null]; $json=json_decode($resp,true); if(!$json) return ['success'=>false,'error'=>'invalid json','raw'=>$resp]; return $json+['raw'=>$resp]; }

// V1 valid JPEG
$v1=$workDir.'/valid1.jpg'; makeImage($v1,320,200); $results['V1']=uploadFile($v1,$cookieFile);
// V2 PNG (convert)
$v2=$workDir.'/valid2.png'; $im=imagecreatetruecolor(200,150); $bg=imagecolorallocate($im,30,180,90); imagefilledrectangle($im,0,0,200,150,$bg); imagepng($im,$v2); imagedestroy($im); $results['V2']=uploadFile($v2,$cookieFile);
// V3 batch sequential
$batchSeq=[]; for($i=0;$i<5;$i++){ $p=$workDir."/seq_$i.jpg"; makeImage($p,200+10*$i,150+5*$i); $batchSeq[$i]=uploadFile($p,$cookieFile,['title'=>'Seq'.$i]); }
$results['V3']=$batchSeq;
// V4 batch parallel
$mh=curl_multi_init(); $handles=[]; $parallelPaths=[]; for($i=0;$i<5;$i++){ $p=$workDir."/par_$i.jpg"; $parallelPaths[]=$p; makeImage($p,220,160); $h=curl_init(); $fields=[ 'image'=>new CURLFile($p,'image/jpeg',basename($p)),'folder'=>'default','title'=>'Par'.$i,'description'=>'Parallel','tags'=>'parallel' ]; curl_setopt_array($h,[CURLOPT_URL=>$apiUrl,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$fields,CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookieFile,CURLOPT_COOKIEJAR=>$cookieFile]); curl_multi_add_handle($mh,$h); $handles[]=$h; }
$running=null; do{ curl_multi_exec($mh,$running); curl_multi_select($mh); } while($running>0);
$parallelRes=[]; foreach($handles as $h){ $resp=curl_multi_getcontent($h); $json=json_decode($resp,true); $parallelRes[]=$json?:['success'=>false,'error'=>'invalid json','raw'=>$resp]; curl_multi_remove_handle($mh,$h); curl_close($h);} curl_multi_close($mh); $results['V4']=$parallelRes;
// E1 missing file field
$ch=curl_init(); curl_setopt_array($ch,[CURLOPT_URL=>$apiUrl,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>['folder'=>'default'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookieFile,CURLOPT_COOKIEJAR=>$cookieFile]); $resp=curl_exec($ch); curl_close($ch); $results['E1']=json_decode($resp,true)?:['success'=>false,'error'=>'invalid json','raw'=>$resp];
// E2 unsupported mime (SVG)
$svg=$workDir.'/bad.svg'; file_put_contents($svg,'<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10" fill="red"/></svg>'); $results['E2']=uploadFile($svg,$cookieFile);
// E3 oversized file >5MB
$big=$workDir.'/big.jpg'; // create large blank image ~6MB
$w=3000;$h=3000; $im=imagecreatetruecolor($w,$h); $c=imagecolorallocate($im,120,40,200); imagefilledrectangle($im,0,0,$w,$h,$c); imagejpeg($im,$big,90); imagedestroy($im); $results['E3']=uploadFile($big,$cookieFile);
// E4 corrupted JPEG (truncate)
$cor=$workDir.'/corrupt.jpg'; copy($v1,$cor); $data=file_get_contents($cor); $data=substr($data,0, (int)(strlen($data)/3)); file_put_contents($cor,$data); $results['E4']=uploadFile($cor,$cookieFile);
// E5 wrong enctype (simulate by raw POST not multipart)
$ch=curl_init(); curl_setopt_array($ch,[CURLOPT_URL=>$apiUrl,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],CURLOPT_POSTFIELDS=>'folder=default&title=WrongEnctype',CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookieFile,CURLOPT_COOKIEJAR=>$cookieFile]); $resp=curl_exec($ch); curl_close($ch); $results['E5']=json_decode($resp,true)?:['success'=>false,'error'=>'invalid json','raw'=>$resp];

// Summaries
$summary=[]; foreach($results as $k=>$v){ if(in_array($k,['V3','V4'])){ $ok=count(array_filter($v,function($r){return !empty($r['success']);})); $summary[$k]="$ok/".count($v).' succeeded'; } else { $summary[$k]= empty($v['success'])?('FAIL: '.($v['error']??'unknown')):'OK'; } }

info('--- MATRIX SUMMARY ---'); foreach($summary as $k=>$v){ echo "$k => $v\n"; }

// Output combined JSON
echo json_encode(['summary'=>$summary,'results'=>$results], JSON_PRETTY_PRINT)."\n";

// Cleanup large files (leave workDir for inspection)
@unlink($cookieFile);
// (Keep workDir for manual inspection) echo path
info('Artifacts in: '.$workDir);
