<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\Drivers\Vips\Traits\CanStripMeta;
use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\GifEncoder as GenericGifEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;

class GifEncoder extends GenericGifEncoder implements SpecializedInterface
{
    use CanStripMeta;

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
        try {
            $result = $image->core()->native()->writeToBuffer('.gif', array_merge([
                'interlace' => $this->interlaced,
            ], $this->metaOptions($image)));
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode GIF image format', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_GIF->value);
    }
}
