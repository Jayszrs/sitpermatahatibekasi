<?php
require_once __DIR__.'/config/storage.php';
$relative=rawurldecode(substr($path,strlen('media/')));
$root=realpath(app_upload_root());
$file=$root?realpath($root.DIRECTORY_SEPARATOR.$relative):false;
$allowed=['public','units','units-hero','units-social'];
$extension=strtolower(pathinfo($relative,PATHINFO_EXTENSION));
$mime=['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif','pdf'=>'application/pdf','mp4'=>'video/mp4','webm'=>'video/webm'];
if(!in_array(explode('/',$relative)[0],$allowed,true) || str_contains($relative,'..') || str_contains($relative,'\\')
    || !$file || !is_file($file) || !str_starts_with($file,$root.DIRECTORY_SEPARATOR) || !isset($mime[$extension])){
    http_response_code(404);exit;
}
if(!in_array($_SERVER['REQUEST_METHOD'],['GET','HEAD'],true)){header('Allow: GET, HEAD');http_response_code(405);exit;}
$size=filesize($file);$start=0;$end=$size-1;
header('Content-Type: '.$mime[$extension]);header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');header('Accept-Ranges: bytes');
if(isset($_SERVER['HTTP_RANGE'])){
    if(!preg_match('/^bytes=(\d*)-(\d*)$/',$_SERVER['HTTP_RANGE'],$match) || ($match[1]===''&&$match[2]==='')){
        header('Content-Range: bytes */'.$size);http_response_code(416);exit;
    }
    if($match[1]===''){$start=max(0,$size-(int)$match[2]);}
    else{$start=(int)$match[1];if($match[2]!=='')$end=min($end,(int)$match[2]);}
    if($start>$end || $start>=$size){header('Content-Range: bytes */'.$size);http_response_code(416);exit;}
    http_response_code(206);header("Content-Range: bytes $start-$end/$size");
}
$length=max(0,$end-$start+1);header('Content-Length: '.$length);
if($_SERVER['REQUEST_METHOD']==='HEAD')exit;
$handle=fopen($file,'rb');fseek($handle,$start);
while($length>0 && !feof($handle)){ $chunk=fread($handle,min(65536,$length));if($chunk===false)break;echo $chunk;$length-=strlen($chunk); }
fclose($handle);
