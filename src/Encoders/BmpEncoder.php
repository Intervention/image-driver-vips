<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\BmpEncoder as GenericBmpEncoder;
use Intervention\Image\Interfaces\ImageInterface;

class BmpEncoder extends GenericBmpEncoder
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

        $result = $vipsImage->writeToBuffer('.bmp');

        return new EncodedImage($result, 'image/bmp');
    }
}
