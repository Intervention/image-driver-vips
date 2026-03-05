<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\TiffEncoder as GenericTiffEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Config as VipsConfig;
use Jcupitt\Vips\ForeignKeep;
use Jcupitt\Vips\Exception as VipsException;

class TiffEncoder extends GenericTiffEncoder implements SpecializedInterface
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
        try {
            $result = $image->core()->native()->writeToBuffer('.tiff', $this->options());
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode TIFF image format', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_TIFF->value);
    }

    /**
     * @throws StateException
     * @return array{lossless: bool, Q: int, keep?: int, strip?: bool}
     */
    private function options(): array
    {
        $options = [
            'lossless' => $this->quality === 100,
            'Q' => $this->quality,
        ];

        $strip = $this->strip || $this->driver()->config()->strip;

        if (VipsConfig::atLeast(8, 15)) {
            $options['keep'] = $strip ? ForeignKeep::ICC : ForeignKeep::ALL;
        } else {
            $options['strip'] = true;
        }

        return $options;
    }
}
