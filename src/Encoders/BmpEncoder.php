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
     * @see EncoderInterface::function()
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $result = $image->core()->native()->writeToBuffer('.bmp', [
            'strip' => true,
        ]);

        return new EncodedImage($result, 'image/bmp');
    }
}
