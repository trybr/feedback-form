<?php
session_start();

$code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);
$_SESSION["captcha_code"] = $code;

$width = 130;
$height = 44;

$img = imagecreatetruecolor($width, $height);
$bg = imagecolorallocate($img, 245, 247, 250);
imagefill($img, 0, 0, $bg);

// шумовые линии
for ($i = 0; $i < 6; $i++) {
    $color = imagecolorallocate(
        $img,
        rand(180, 220),
        rand(180, 220),
        rand(180, 220),
    );
    imageline(
        $img,
        rand(0, $width),
        rand(0, $height),
        rand(0, $width),
        rand(0, $height),
        $color,
    );
}

// шумовые точки
for ($i = 0; $i < 120; $i++) {
    $color = imagecolorallocate(
        $img,
        rand(150, 220),
        rand(150, 220),
        rand(150, 220),
    );
    imagesetpixel($img, rand(0, $width), rand(0, $height), $color);
}

// сам код
$font = 5;
$charW = imagefontwidth($font);
$totalW = $charW * strlen($code);
$startX = (int) (($width - $totalW) / 2);
$y = (int) (($height - imagefontheight($font)) / 2);

for ($i = 0; $i < strlen($code); $i++) {
    $color = imagecolorallocate($img, rand(20, 90), rand(20, 90), rand(20, 90));
    $x = $startX + $i * $charW;
    $offsetY = rand(-4, 4);
    imagestring($img, $font, $x, $y + $offsetY, $code[$i], $color);
}

header("Content-Type: image/png");
header("Cache-Control: no-store, no-cache, must-revalidate");
imagepng($img);
imagedestroy($img);
