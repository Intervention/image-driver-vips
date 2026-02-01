<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\EncodedImageInterface;

class EncodedImageObjectDecoder extends BinaryImageDecoder
{
    /**
     * {@inheritdoc}
     *
     * @see DecoderInterface::supports()
     */
    public function supports(mixed $input): bool
    {
        return $input instanceof EncodedImageInterface;
    }

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::decode()
     */
    public function decode(mixed $input): ImageInterface
    {
        if (!$input instanceof EncodedImageInterface) {
            throw new InvalidArgumentException('Image source must be of type ' . EncodedImage::class);
        }

        try {
            return parent::decode($input->toString());
        } catch (DecoderException) {
            throw new ImageDecoderException(EncodedImage::class . ' contains unsupported image type');
        }
    }
}
