<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\Drivers\Vips\Traits\CanStripMeta;
use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\Jpeg2000Encoder as GenericJpeg2000Encoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;

class Jpeg2000Encoder extends GenericJpeg2000Encoder implements SpecializedInterface
{
    use CanStripMeta;

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     *
     * @throws InvalidArgumentException
     * @throws StateException
     * @throws StreamException
     * @throws EncoderException
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $vipsImage = $image->core()->native();

        if ($image->isAnimated()) {
            $vipsImage = $image->core()->frame(0)->native();
        }

        try {
            $result = $vipsImage->writeToBuffer('.j2k', $this->options($image));
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode Jpeg2000 image format', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_JP2->value);
    }

    /**
     * @throws StateException
    * @return array{lossless: bool, Q: int, keep?: int, strip?: bool}
    */
    private function options(ImageInterface $image): array
    {
        return array_merge([
            'lossless' => $this->quality === 100,
            'Q' => $this->quality,
        ], $this->metaOptions($image, $this->strip));
    }
}
