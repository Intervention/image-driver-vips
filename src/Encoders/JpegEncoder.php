<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\Colors\Rgb\Colorspace as Rgb;
use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\JpegEncoder as GenericJpegEncoder;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Config as VipsConfig;
use Jcupitt\Vips\ForeignKeep;

class JpegEncoder extends GenericJpegEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $vipsImage = $image->core()->native();

        if ($image->isAnimated()) {
            $vipsImage = $image->core()->frame(0)->native();
        }

        $result = $vipsImage->writeToBuffer('.jpg', $this->getOptions());

        return new EncodedImage($result, 'image/jpeg');
    }

    /**
     * @throws RuntimeException
     *
     * @return array{Q: int, optimize_coding: bool, background: array<int>, keep?: int, strip?: bool}
     */
    protected function getOptions(): array
    {
        $options = [
            'Q' => $this->quality,
            'interlace' => $this->progressive,
            'optimize_coding' => true,
            'background' => array_slice($this->driver()->handleInput(
                $this->driver()->config()->blendingColor
            )->convertTo(Rgb::class)->toArray(), 0, 3),
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
