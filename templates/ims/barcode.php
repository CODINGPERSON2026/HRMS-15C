<?php
if (!isset($_GET['code']) || empty($_GET['code'])) {
    exit;
}

$code = $_GET['code'];

header("Content-Type: image/png");

$width = 2;
$height = 60;

$im = imagecreate(strlen($code) * 11 * $width + 20, $height);

$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);

$x = 10;

for ($i = 0; $i < strlen($code); $i++) {

    $ascii = ord($code[$i]);

    for ($j = 0; $j < 8; $j++) {

        if (($ascii >> $j) & 1) {
            imagefilledrectangle(
                $im,
                $x,
                0,
                $x + $width,
                $height,
                $black
            );
        }

        $x += $width;
    }

    $x += $width;
}

/* ❌ Removed Text Printing */

imagepng($im);
imagedestroy($im);
?>