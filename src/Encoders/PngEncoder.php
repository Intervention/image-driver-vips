<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\PngEncoder as GenericPngEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class PngEncoder extends GenericPngEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $vipsImage = $image->core()->native();

        if ($image->isAnimated()) {
            $vipsImage = $image->core()->frame(0)->native();
        }

        $result = $vipsImage->writeToBuffer('.png', [
            'interlace' => $this->interlaced,
            'palette' => $this->indexed,
        ]);

        return new EncodedImage($result, 'image/png');
    }
}
