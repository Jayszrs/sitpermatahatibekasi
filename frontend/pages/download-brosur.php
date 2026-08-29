<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$slug = strtolower(trim($_GET['unit'] ?? ''));
$stmt=$pdo->prepare('SELECT unit_slug,unit_name,headline,description,file_url FROM brochures WHERE unit_slug=? AND is_active=1 LIMIT 1'); $stmt->execute([$slug]); $brochure=$stmt->fetch();
if(!$brochure) { http_response_code(404); exit('Brosur tidak ditemukan.'); }
if(!$brochure['file_url']) {
    $clean = static fn(string $text): string => str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)',' ',' '],iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$text) ?: $text);
    $lines = array_merge(['SIT PERMATA HATI BEKASI','BROSUR '.$brochure['unit_name'],'',$brochure['headline'],''],wordwrap($brochure['description'],72,"\n",true) ? explode("\n",wordwrap($brochure['description'],72,"\n",true)) : [],['','Informasi lengkap: '.SITE_URL.'/brosur-unit.php?unit='.$brochure['unit_slug'],'Pendaftaran: '.SITE_URL.'/form-spmb.php?level='.rawurlencode($brochure['unit_name'])]);
    $content="BT\n/F1 20 Tf\n72 770 Td\n"; foreach($lines as $index=>$line){ if($index===1)$content.="/F1 16 Tf\n"; elseif($index===3)$content.="/F1 14 Tf\n"; elseif($index===5)$content.="/F1 11 Tf\n"; $content.='('.$clean((string)$line).") Tj\n0 -24 Td\n"; } $content.="ET";
    $objects=[1=>'<< /Type /Catalog /Pages 2 0 R >>',2=>'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',3=>'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',4=>'<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream",5=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'];
    $pdf="%PDF-1.4\n"; $offsets=[0=>0]; foreach($objects as $number=>$object){$offsets[$number]=strlen($pdf);$pdf.=$number." 0 obj\n".$object."\nendobj\n";} $xref=strlen($pdf); $pdf.="xref\n0 6\n0000000000 65535 f \n"; for($i=1;$i<=5;$i++)$pdf.=sprintf('%010d 00000 n ', $offsets[$i])."\n"; $pdf.="trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="brosur-'.$brochure['unit_slug'].'.pdf"'); header('Content-Length: '.strlen($pdf)); header('X-Content-Type-Options: nosniff'); echo $pdf; exit;
}
$urlPath=(string)parse_url($brochure['file_url'],PHP_URL_PATH);
$relative='';
foreach(['/school-website/frontend/assets/uploads/'=>'../assets/uploads/','/school-website/frontend/assets/brochures/'=>'../assets/brochures/'] as $prefix=>$directory){ if(strpos($urlPath,$prefix)===0){$relative=$directory.basename($urlPath);break;} }
if($relative===''){ header('Location: '.$brochure['file_url']); exit; }
$target=realpath(__DIR__.'/'.$relative);
if(!$target || !is_file($target) || strtolower(pathinfo($target,PATHINFO_EXTENSION))!=='pdf'){ http_response_code(404); exit('File brosur belum tersedia.'); }
header('Content-Type: application/pdf'); header('Content-Length: '.filesize($target)); header('Content-Disposition: attachment; filename="brosur-'.preg_replace('/[^a-z0-9-]+/i','-',$brochure['unit_slug']).'.pdf"'); header('X-Content-Type-Options: nosniff'); readfile($target); exit;
