<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\Drivers\Vips\Traits\CanStripMeta;
use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\JxlEncoder as GenericJxlEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;

class JxlEncoder extends GenericJxlEncoder implements SpecializedInterface
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
            $result = $image->core()->native()->writeToBuffer('.jxl', $this->options($image));
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode JXL image format', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_JXL->value);
    }

    /**
     * libvips' jxlsave has a native lossless flag. JXL has no separate lossless
     * setting beyond maximum quality, so it is enabled at Q 100, matching the
     * AVIF and HEIC encoders of this driver.
     *
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
