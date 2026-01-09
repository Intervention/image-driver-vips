<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\JpegEncoder as GenericJpegEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\FilePointerException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Config as VipsConfig;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\ForeignKeep;

class JpegEncoder extends GenericJpegEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     *
     * @throws InvalidArgumentException
     * @throws EncoderException
     * @throws FilePointerException
     * @throws StateException
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $vipsImage = $image->core()->native();

        if ($image->isAnimated()) {
            $vipsImage = $image->core()->frame(0)->native();
        }

        try {
            $result = $vipsImage->writeToBuffer('.jpg', $this->options($image));
        } catch (VipsException $e) {
            throw new EncoderException('Failed to encode JPEG image format', previous: $e);
        }

        return new EncodedImage($result, MediaType::IMAGE_JPEG->value);
    }

    /**
     * @throws StateException
     * @return array{Q: int, interlace: bool, optimize_coding: true, background: array<float>, keep: 8|63}
     */
    private function options(ImageInterface $image): array
    {
        $options = [
            'Q' => $this->quality,
            'interlace' => $this->progressive,
            'optimize_coding' => true,
            'background' => $this->backgroundColor($image),
        ];

        $strip = $this->strip || $this->driver()->config()->strip;

        if (VipsConfig::atLeast(8, 15)) {
            $options['keep'] = $strip ? ForeignKeep::ICC : ForeignKeep::ALL;
        } else {
            $options['strip'] = $strip;
        }

        return $options;
    }

    /**
     * Decode background color to cover possible transparent areas of image in JPEG format without alpha.
     *
     * @throws StateException
     * @return array<float>
     */
    private function backgroundColor(ImageInterface $image): array
    {
        $bgColor = $this->driver()->colorProcessor($image)->colorToNative(
            $this->driver()->handleColorInput(
                $this->driver()->config()->backgroundColor
            )
        );

        // remove alpha channel to make sure only 1 or 3 bands are returned for resulting JPEG
        return count($bgColor) === 4 ? array_slice($bgColor, 0, 3) : $bgColor;
    }
}
