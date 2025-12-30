<?php

namespace Modules\Core\Helpers;

use Imagick;
use ImagickDraw;
use RuntimeException;

class ImageWatermarkHelper
{
    public static function addWatermark(
        string $imagePath,
        string $text/* = generalSetting()->watermark_text */,
        array $options = []
    ): string {
        if (! extension_loaded('imagick')) {
            throw new RuntimeException('Imagick extension is not installed');
        }

        // Default options
        $defaults = [
            'position' => generalSetting()->watermark_position,
            'opacity' => 0.7,
            'fontSize' => 30,
            'font' => 'Arial',
            'outputPath' => null,
        ];

        $options = array_merge($defaults, $options);

        // Create Imagick instance
        $image = new Imagick($imagePath);

        // Create text layer
        $draw = new ImagickDraw;
        $draw->setFont($options['font']);
        $draw->setFontSize($options['fontSize']);
        $draw->setFillOpacity($options['opacity']);
        $draw->setGravity(Imagick::GRAVITY_SOUTHEAST);

        // Get image dimensions
        $dimensions = $image->getImageGeometry();

        // Calculate position
        switch ($options['position']) {
            case 'center':
                $draw->setGravity(Imagick::GRAVITY_CENTER);
                break;
            case 'top-right':
                $draw->setGravity(Imagick::GRAVITY_NORTHEAST);
                break;
            default: // bottom-right
                $draw->setGravity(Imagick::GRAVITY_SOUTHEAST);
        }

        // Add watermark
        $image->annotateImage(
            $draw,
            10,
            10,
            0,
            $text
        );

        // Set output path
        $outputPath = $options['outputPath'] ??
            pathinfo($imagePath, PATHINFO_DIRNAME).'/watermarked_'.
            time().'.'.$image->getImageFormat();

        // Save image
        $image->writeImage($outputPath);

        return $outputPath;
    }
}
