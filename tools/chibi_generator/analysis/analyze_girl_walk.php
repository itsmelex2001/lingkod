<?php
$files = glob(__DIR__ . '/public/assets/walk_girl/frame_*.png');
sort($files);
echo "Total frames in walk_girl: " . count($files) . "\n";
foreach ($files as $f) {
    $im = imagecreatefrompng($f);
    $w = imagesx($im);
    $h = imagesy($im);
    $minX = 9999; $maxX = 0; $minY = 9999; $maxY = 0;
    // Inspect girl area
    for ($y = 0; $y < $h; $y += 4) {
        for ($x = 420; $x < $w; $x += 4) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            if ($r < 235 || $g < 235 || $b < 235) {
                if ($x < $minX) $minX = $x;
                if ($x > $maxX) $maxX = $x;
                if ($y < $minY) $minY = $y;
                if ($y > $maxY) $maxY = $y;
            }
        }
    }
    // Also check bottom feet area (y from 550 to 710) across entire width
    $feetMinX = 9999; $feetMaxX = 0;
    for ($y = 560; $y < 700; $y += 2) {
        for ($x = 420; $x < 850; $x += 2) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            // Shoes are white/gray or green pants
            // check non background
            if (abs($r - 210) > 20 || abs($g - 213) > 20 || abs($b - 215) > 20) {
                if ($x < $feetMinX) $feetMinX = $x;
                if ($x > $feetMaxX) $feetMaxX = $x;
            }
        }
    }
    echo basename($f) . ": Girl X=$minX..$maxX Y=$minY..$maxY | Feet X=$feetMinX..$feetMaxX\n";
    imagedestroy($im);
}
