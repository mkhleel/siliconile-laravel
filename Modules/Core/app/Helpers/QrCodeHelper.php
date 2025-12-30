<?php

namespace Modules\Core\Helpers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeHelper
{
    /**
     * Generate QR Code
     *
     * @param  string  $data
     */
    public static function generate($data, array $options = []): string
    {
        $size = $options['size'] ?? 300;
        $margin = $options['margin'] ?? 10;

        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        $svg = $writer->writeString($data);

        $svgBase64 = base64_encode($svg);

        return 'data:image/svg+xml;base64,'.$svgBase64;

        // Generate QR code and return as base64 string
        //        ob_start();
        //        $writer->writeString($data);
        //        $qrCode = ob_get_contents();
        //        ob_end_clean();
        //
        //        return base64_encode($qrCode);
    }
}
