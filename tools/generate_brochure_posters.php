<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$sourceDir = $root . '/frontend/assets/images/brochures';
$pdfDir = $root . '/frontend/assets/brochures';
if (!is_dir($pdfDir)) mkdir($pdfDir, 0775, true);

$regular = 'C:/Windows/Fonts/arial.ttf';
$bold = 'C:/Windows/Fonts/arialbd.ttf';
$logoPath = $root . '/frontend/assets/images/logo-sit-round.png';
$green = [7, 67, 42]; $gold = [220, 181, 43]; $cream = [248, 246, 238];
$units = [
    'daycare' => ['DAYCARE PERMATA HATI','Tumbuh Nyaman Sejak Langkah Pertama','Stimulasi sensorik | Pembiasaan adab | Pendampingan tumbuh kembang','Untuk anak usia 1-4 tahun'],
    'tkit' => ['TKIT PERMATA HATI','Bermain, Belajar, dan Beradab','Pembelajaran sentra | Tahsin dasar | Kreativitas dan kemandirian','Untuk anak usia 4-6 tahun'],
    'sdit' => ['SDIT PERMATA HATI','Fondasi Akademik dan Karakter yang Kuat','Literasi numerasi | Tahfidz dan tahsin | Project based learning','Pendidikan dasar Islam terpadu'],
    'smpit' => ['SMPIT PERMATA HATI','Siap Memimpin dan Berkarya','Tahfidz lanjutan | Sains teknologi | Leadership project','Pendidikan remaja muslim masa depan'],
];

function fitText($image, string $text, string $font, int $maxSize, int $minSize, int $maxWidth): int {
    for ($size=$maxSize; $size>=$minSize; $size--) {
        $box=imagettfbbox($size,0,$font,$text);
        if (($box[2]-$box[0]) <= $maxWidth) return $size;
    }
    return $minSize;
}
function wrapText($image, string $text, string $font, int $size, int $maxWidth): array {
    $words=preg_split('/\s+/',trim($text)); $lines=[]; $line='';
    foreach($words as $word){ $test=trim($line.' '.$word); $box=imagettfbbox($size,0,$font,$test); if($line!==''&&($box[2]-$box[0])>$maxWidth){$lines[]=$line;$line=$word;}else{$line=$test;} }
    if($line!=='')$lines[]=$line; return $lines;
}
function writePdfFromJpeg(string $jpegPath, string $pdfPath): void {
    [$width,$height]=getimagesize($jpegPath); $jpg=file_get_contents($jpegPath);
    $pageW=595; $pageH=842; $content="q\n$pageW 0 0 $pageH 0 0 cm\n/Im0 Do\nQ";
    $objects=[
        1=>'<< /Type /Catalog /Pages 2 0 R >>',
        2=>'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        3=>'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /XObject << /Im0 5 0 R >> >> /Contents 4 0 R >>',
        4=>'<< /Length '.strlen($content).">>\nstream\n$content\nendstream",
        5=>'<< /Type /XObject /Subtype /Image /Width '.$width.' /Height '.$height.' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpg).">>\nstream\n".$jpg."\nendstream",
    ];
    $pdf="%PDF-1.4\n"; $offsets=[0];
    foreach($objects as $number=>$object){$offsets[$number]=strlen($pdf);$pdf.=$number." 0 obj\n".$object."\nendobj\n";}
    $xref=strlen($pdf); $pdf.="xref\n0 6\n0000000000 65535 f \n";
    for($i=1;$i<=5;$i++)$pdf.=sprintf('%010d 00000 n ',$offsets[$i])."\n";
    $pdf.="trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    file_put_contents($pdfPath,$pdf);
}

foreach($units as $slug=>$copy){
    $source=imagecreatefrompng($sourceDir.'/'.$slug.'-promo.png');
    $canvas=imagecreatetruecolor(1240,1754); imagealphablending($canvas,true);
    $srcW=imagesx($source); $srcH=imagesy($source); $targetH=1120; $scale=max(1240/$srcW,$targetH/$srcH); $cropW=(int)(1240/$scale); $cropH=(int)($targetH/$scale); $srcX=(int)(($srcW-$cropW)/2); $srcY=max(0,(int)($srcH-$cropH));
    imagecopyresampled($canvas,$source,0,0,$srcX,$srcY,1240,$targetH,$cropW,$cropH);
    $greenColor=imagecolorallocate($canvas,...$green); $goldColor=imagecolorallocate($canvas,...$gold); $creamColor=imagecolorallocate($canvas,...$cream); $white=imagecolorallocate($canvas,255,255,255);
    imagefilledrectangle($canvas,0,1040,1240,1754,$greenColor);
    for($y=750;$y<1120;$y++){ $alpha=(int)(127*(1-($y-750)/370)); $overlay=imagecolorallocatealpha($canvas,$green[0],$green[1],$green[2],max(0,min(127,$alpha))); imageline($canvas,0,$y,1240,$y,$overlay); }
    imagefilledrectangle($canvas,72,1010,545,1062,$goldColor);
    imagettftext($canvas,24,0,94,1047,$greenColor,$bold,'PENERIMAAN SISWA BARU');
    $titleSize=fitText($canvas,$copy[0],$bold,52,36,1040); imagettftext($canvas,$titleSize,0,88,1165,$goldColor,$bold,$copy[0]);
    $headlineLines=wrapText($canvas,$copy[1],$bold,66,1050); $y=1270; foreach($headlineLines as $line){imagettftext($canvas,66,0,88,$y,$white,$bold,$line);$y+=78;}
    imagettftext($canvas,28,0,90,$y+18,$creamColor,$regular,$copy[3]);
    $featureY=$y+92; foreach(explode(' | ',$copy[2]) as $feature){imagefilledellipse($canvas,100,$featureY-9,10,10,$goldColor);imagettftext($canvas,25,0,122,$featureY,$white,$regular,$feature);$featureY+=46;}
    if(is_file($logoPath)){ $logo=imagecreatefrompng($logoPath); imagecopyresampled($canvas,$logo,1012,60,0,0,150,150,imagesx($logo),imagesy($logo)); imagedestroy($logo); }
    imagettftext($canvas,22,0,88,1690,$creamColor,$bold,'SIT PERMATA HATI BEKASI');
    imagettftext($canvas,19,0,88,1722,$creamColor,$regular,'Tambun Selatan, Bekasi  |  sitpermatahatibekasi');
    $jpgPath=$sourceDir.'/'.$slug.'-poster.jpg'; imagejpeg($canvas,$jpgPath,91); imagedestroy($canvas); imagedestroy($source);
    writePdfFromJpeg($jpgPath,$pdfDir.'/brosur-'.$slug.'.pdf');
    echo $slug." poster generated\n";
}
