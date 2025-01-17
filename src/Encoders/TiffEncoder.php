<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\TiffEncoder as GenericTiffEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class TiffEncoder extends GenericTiffEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $result = $image->core()->native()->writeToBuffer('.tiff', [
            'lossless' => false,
            'Q' => $this->quality,
        ]);

        return new EncodedImage($result, 'image/tiff');
    }
}
