<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Drivers\Vips\Traits\CanStripMeta;
use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\JpegEncoder as GenericJpegEncoder;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;

class JpegEncoder extends GenericJpegEncoder implements SpecializedInterface
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
     * @return array{
     *     Q: int,
     *     interlace: bool,
     *     optimize_coding: true,
     *     background?: array<float>,
     *     keep?: int,
     *     strip?: bool
     * }
     */
    private function options(ImageInterface $image): array
    {
        $options = [
            'Q' => $this->quality,
            'interlace' => $this->progressive,
            'optimize_coding' => true,
        ];

        if ($image->core()->native()->hasAlpha()) {
            $options['background'] = $this->backgroundColor($image);
        }

        return array_merge($options, $this->metaOptions($image, $this->strip));
    }

    /**
     * Decode background color to cover possible transparent areas of image in JPEG format without alpha.
     *
     * @throws StateException
     * @return array<float>
     */
    private function backgroundColor(ImageInterface $image): array
    {
        $bgColor = $this->driver()->colorProcessor($image)->export(
            $this->driver()->decodeColor(
                $this->driver()->config()->backgroundColor,
            ),
        );

        return match ($image->colorspace()::class) {
            // If the colorspace is CMYK, remove the alpha channel to make sure only 4 bands are returned.
            CmykColorspace::class => count($bgColor) === 5 ? array_slice($bgColor, 0, 4) : $bgColor,
            // remove alpha channel to make sure only 1 or 3 bands are returned for resulting JPEG
            default => count($bgColor) === 4 ? array_slice($bgColor, 0, 3) : $bgColor,
        };
    }
}
