<?php
include_once 'tifTag.php';
include_once 'tifImageDecode.php';

$imageFileName = "tif/foldr_32.tif";
$tif = new tifTag($imageFileName);
$tif->parse($imageFileName);

$buff = file_get_contents($imageFileName);
$img = $tif->getTags();
$ifds = [$img];

$tid = new tifImageDecode();
//echo "\r\n<br>";
$page = $tid->decodeImage($buff, $img, $ifds);
$rgba = $tid->toRGBA8($page);

header("Content-Type: image/png");
$im = imagecreatetruecolor(32, 32);

$black = ImageColorAllocate($im, 0, 0, 0); // черный цвет
$trans = imagecolortransparent($im, $black); // теперь черный прозрачен

//ImageFill($im, 0, 0, $black); //заливка прозрачным цветом
//$img = imagecreatefrompng('1.png'); //исходное изображение с прозрачностью





//изображение теперь прозрачное - проверял

//$img = $result; //теперь нет черного цвета


$i = 0;
for ($h = 0; $h < 32; $h++) {
  for ($w = 0; $w < 32; $w++) {

    $color = imagecolorallocate ( $im , $rgba[$i] , $rgba[$i+1] , $rgba[$i+2]);
    //$color = imagecolorallocatealpha ( $im , $rgba[$i] , $rgba[$i+1] , $rgba[$i+2], 1 );


    imagesetpixel($im, round($w),round($h), $color);
    $i+=4;
  }
}

//imagecopyresampled($im, $im, 0, 0, 0, 0, 32, 32, 32, 32);

imagepng($im);
imagedestroy($im);
//exit;
?>

<!doctype html><html lang="ru"><head>
	<meta charset="UTF-8"><title>Tif</title>
	<script src="UTIF.js"></script><script src="tif.js"></script>
</head>
<body>

<img id="tifImg" src="" alt="">




</body></html>

<?php
include_once 'tifTag.php';
include_once 'tifImageDecode.php';

$imageFileName = "tif/foldr_32.tif";
$tif = new tifTag($imageFileName);
$tif->parse($imageFileName);

$buff = file_get_contents($imageFileName);
$img = $tif->getTags();
$ifds = [$img];

$tid = new tifImageDecode();
//echo "\r\n<br>";
$page = $tid->decodeImage($buff, $img, $ifds);
$rgba = $tid->toRGBA8($page);
//echo implode(',', $img);

//print_r($tif->getTags());
//print_r($tif->getProperties());

// https://www.loc.gov/preservation/digital/formats/content/tiff_tags.shtml
// https://www.awaresystems.be/imaging/tiff/faq.html
// https://ru.wikipedia.org/wiki/TIFF
// https://www.awaresystems.be/imaging/tiff/specification/TIFF6.pdf
// https://github.com/photopea/UTIF.js



?>
