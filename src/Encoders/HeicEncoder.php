<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\HeicEncoder as GenericHeicEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Config as VipsConfig;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\ForeignKeep;

class HeicEncoder extends GenericHeicEncoder implements SpecializedInterface
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
            $result = $image->core()->native()->writeToBuffer('.heic', $this->options());
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode HEIC image format', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_HEIC->value);
    }

    /**
     * @throws StateException
     * @return array{lossless: bool, Q: int, effort: int, keep?: int, strip?: bool}
     */
    private function options(): array
    {
        $options = [
            'lossless' => $this->quality === 100,
            'Q' => $this->quality,
            // libvips' heifsave defaults to effort=4; 1 encodes ~2-3x faster
            // with near-identical bytes on typical web sources (range 0..9).
            'effort' => 1,
        ];

        $strip = $this->strip || $this->driver()->config()->strip;

        if (VipsConfig::atLeast(8, 15)) {
            $keepAll = VipsConfig::atLeast(8, 18)
                ? ForeignKeep::ALL
                : ForeignKeep::ALL & ~ForeignKeep::GAINMAP;
            $options['keep'] = $strip ? ForeignKeep::ICC : $keepAll;
        } else {
            $options['strip'] = $strip;
        }

        return $options;
    }
}
