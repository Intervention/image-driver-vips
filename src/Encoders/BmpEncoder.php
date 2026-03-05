<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\BmpEncoder as GenericBmpEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;

class BmpEncoder extends GenericBmpEncoder
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     *
     * @throws InvalidArgumentException
     * @throws EncoderException
     * @throws StreamException
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $vipsImage = $image->core()->native();

        if ($image->isAnimated()) {
            $vipsImage = $image->core()->frame(0)->native();
        }

        try {
            $result = $vipsImage->writeToBuffer('.bmp');
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode BMP image format', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_BMP->value);
    }
}
