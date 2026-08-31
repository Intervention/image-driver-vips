<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\Drivers\Vips\Traits\CanStripMeta;
use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\WebpEncoder as GenericWebpEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;

class WebpEncoder extends GenericWebpEncoder implements SpecializedInterface
{
    use CanStripMeta;

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     *
     * @throws InvalidArgumentException
     * @throws StateException
     * @throws EncoderException
     * @throws StreamException
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        try {
            $result = $image->core()->native()->writeToBuffer('.webp', $this->options($image));
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode WEBP image format ', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_WEBP->value);
    }

    /**
     * @throws StateException
     * @return array{lossless: bool, Q: int, effort: int, keep?: int, strip?: bool}
     */
    private function options(ImageInterface $image): array
    {
        return array_merge([
            'lossless' => $this->quality === 100,
            'Q' => $this->quality,
            // libvips' webpsave defaults to effort=4; 2 roughly halves encode
            // time while staying within ~3% bytes (effort=1 costs +10-19%;
            // range 0..6).
            'effort' => 2,
        ], $this->metaOptions($image, $this->strip));
    }
}
