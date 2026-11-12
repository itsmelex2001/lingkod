<?php
$files = [
    'public/assets/boy_walk_spritesheet.png',
    'public/assets/girl_walk_spritesheet.png',
    'public/assets/girl_idle_spritesheet.png',
    'public/assets/girl_wave_spritesheet.png',
    'public/assets/chibi_boy_full.png',
    'public/assets/chibi_girl_full.png'
];

foreach ($files as $f) {
    if (file_exists($f)) {
        $sz = getimagesize($f);
        echo "$f: {$sz[0]}x{$sz[1]}\n";
    } else {
        echo "$f: NOT FOUND\n";
    }
}

