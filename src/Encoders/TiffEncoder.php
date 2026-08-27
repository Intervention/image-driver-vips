<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\Drivers\Vips\Traits\CanStripMeta;
use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\TiffEncoder as GenericTiffEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;

class TiffEncoder extends GenericTiffEncoder implements SpecializedInterface
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
            $result = $image->core()->native()->writeToBuffer('.tiff', $this->options($image));
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode TIFF image format', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_TIFF->value);
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
