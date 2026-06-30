<?php

$source = __DIR__ . '/assets/images/logo.svg';
$outputDir = __DIR__ . '/assets/icons';

$sizes = [
    72,
    96,
    128,
    144,
    152,
    192,
    384,
    512
];

if (!extension_loaded('imagick')) {
    die("Imagick extension is not installed.\n");
}

if (!file_exists($source)) {
    die("Source SVG not found:\n$source\n");
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

foreach ($sizes as $size) {

    $image = new Imagick();

    // Improve SVG rendering
    $image->setBackgroundColor(new ImagickPixel('transparent'));
    $image->setResolution(300, 300);

    $image->readImage($source);

    $image->setImageFormat('png');
    $image->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1, true);

    $filename = $outputDir . "/icon-{$size}x{$size}.png";

    $image->writeImage($filename);
    $image->clear();
    $image->destroy();

    echo "Created: assets/icons/icon-{$size}x{$size}.png<br>";
}

echo "<br><strong>Done!</strong>";