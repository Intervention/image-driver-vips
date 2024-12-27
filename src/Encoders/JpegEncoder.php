<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\JpegEncoder as GenericJpegEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class JpegEncoder extends GenericJpegEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see EncoderInterface::function()
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        // $blendingColor = $this->driver()->handleInput(
        //     $this->driver()->config()->blendingColor
        // );

        // $blendingColor = $this->driver()
        //     ->colorProcessor($image->colorspace())
        //     ->colorToNative($blendingColor);

        $result = $image->core()->native()->writeToBuffer('.jpg', [
            'Q' => $this->quality,
            'interlace' => $this->progressive,
            // 'background' => $blendingColor->toArray(),
        ]);

        return new EncodedImage($result, 'image/jpeg');
    }
}
