<?php
require_once __DIR__ . '/../../backend/config/database.php';
$slug = strtolower(trim($_GET['unit'] ?? ''));
$stmt=$pdo->prepare('SELECT unit_slug,unit_name,file_url FROM brochures WHERE unit_slug=? AND is_active=1 LIMIT 1'); $stmt->execute([$slug]); $brochure=$stmt->fetch();
if(!$brochure || !$brochure['file_url']) { header('Location: brosur-unit.php?unit='.rawurlencode($slug).'&download=unavailable'); exit; }
$urlPath=(string)parse_url($brochure['file_url'],PHP_URL_PATH); $prefix='/school-website/frontend/assets/uploads/';
if(strpos($urlPath,$prefix)!==0){ header('Location: '.$brochure['file_url']); exit; }
$filename=basename($urlPath); $uploadDir=realpath(__DIR__.'/../assets/uploads'); $target=$uploadDir ? $uploadDir.DIRECTORY_SEPARATOR.$filename : '';
if(!$uploadDir || !is_file($target) || strtolower(pathinfo($target,PATHINFO_EXTENSION))!=='pdf'){ http_response_code(404); exit('File brosur belum tersedia.'); }
header('Content-Type: application/pdf'); header('Content-Length: '.filesize($target)); header('Content-Disposition: attachment; filename="brosur-'.preg_replace('/[^a-z0-9-]+/i','-',$brochure['unit_slug']).'.pdf"'); header('X-Content-Type-Options: nosniff'); readfile($target); exit;
