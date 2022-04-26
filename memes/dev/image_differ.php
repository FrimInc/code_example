<?php
error_reporting(E_ALL - E_NOTICE);

function getDiff($img)
{
    if (!($img = imagecreatefromjpeg($img))) {
        $img = imagecreatefrompng($img);
    }

    $colors  = array();
    $resized = imagescale($img, 128, -1, IMG_NEAREST_NEIGHBOUR);
    list($X, $Y) = array(imagesx($resized), imagesy($resized));


    $ar_image_source_t = array();
    $arCheckImage      = array();

    for ($_x = 1; $_x < $X; $_x++) {
        for ($_y = 1; $_y < $Y; $_y++) {
            $C = imagecolorat($resized, $_x, $_y);

            $ar_image_source_t[$_x][$_y] = ($colors[$C] ?: ($colors[$C] = floor(
                (
                    ($C >> 16 & 0xFF) +
                    ($C >> 8) & 0xFF +
                    $C & 0xFF
                ) / 3
            )));
        }
    }

    for ($_x = 2; $_x < $X - 1; $_x++) {
        for ($_y = 2; $_y < $Y - 1; $_y++) {
            $arCheckImage[$_x][$_y] = max($F = array(
                    $ar_image_source_t[$_x][$_y],
                    $ar_image_source_t[$_x][$_y - 1],
                    $ar_image_source_t[$_x - 1][$_y],
                    $ar_image_source_t[$_x - 1][$_y - 1],

                    $ar_image_source_t[$_x][$_y + 1],
                    $ar_image_source_t[$_x + 1][$_y],
                    $ar_image_source_t[$_x + 1][$_y + 1],

                    $ar_image_source_t[$_x - 1][$_y + 1],
                    $ar_image_source_t[$_x + 1][$_y - 1]

                )) - min($F);
        }
    }

    $arImages = scandir($D = __DIR__ . '/indexes/');
    unset($arImages[0]);
    unset($arImages[1]);
    $diff = 1000000000000000;

    $minImg = false;
    foreach ($arImages as $_imf) {
        if ($arCompareImage = json_decode(file_get_contents($D . $_imf), true)) {

            $_cd = 0;

            foreach ($arCheckImage as $_x => $YYs) {
                foreach ($YYs as $_y => $C) {
                    $_cd += max(array($C, $arCompareImage[$_x][$_y])) - min(array($C, $arCompareImage[$_x][$_y]));
                }
            }

            if ($_cd < $diff) {
                $diff   = $_cd;
                $minImg = $arCompareImage;
                if ($diff == 0) {
                    break;
                }
            }


        }
    }
    $DIFF=100;
    if ($minImg) {

        $DIFF = 100-$diff/count($minImg)*count($minImg[2])*255;

    }
    return $DIFF;
}
