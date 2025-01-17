<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\Colors\Rgb\Colorspace as Rgb;
use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\JpegEncoder as GenericJpegEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class JpegEncoder extends GenericJpegEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $blendingColor = $this->driver()->handleInput(
            $this->driver()->config()->blendingColor
        );

        $vipsImage = $image->core()->native();

        if ($image->isAnimated()) {
            $vipsImage = $image->core()->frame(0)->native();
        }

        $result = $vipsImage->writeToBuffer('.jpg', [
            'Q' => $this->quality,
            'interlace' => $this->progressive,
            'optimize_coding' => true,
            'background' => array_slice($blendingColor->convertTo(Rgb::class)->toArray(), 0, 3),
        ]);

        return new EncodedImage($result, 'image/jpeg');
    }
}
