<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\PngEncoder as GenericPngEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class PngEncoder extends GenericPngEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see EncoderInterface::function()
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $core = $image->core()->native();

        if ($image->isAnimated()) {
            $core = $image->core()->frame(1)->native();
        }

        $result = $core->writeToBuffer('.png', [
            'interlace' => $this->interlaced,
            'palette' => $this->indexed,
            'strip' => true,
        ]);

        return new EncodedImage($result, 'image/png');
    }
}
