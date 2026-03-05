<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\PngEncoder as GenericPngEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;

class PngEncoder extends GenericPngEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     *
     * @throws InvalidArgumentException
     * @throws EncoderException
     * @throws StreamException
     * @throws StateException
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $vipsImage = $image->core()->native();

        if ($image->isAnimated()) {
            $vipsImage = $image->core()->frame(0)->native();
        }

        try {
            $result = $vipsImage->writeToBuffer('.png', [
                'interlace' => $this->interlaced,
                'palette' => $this->indexed,
            ]);
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode PNG image format', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_PNG->value);
    }
}
