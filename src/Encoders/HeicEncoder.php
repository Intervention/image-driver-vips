<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\HeicEncoder as GenericHeicEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class HeicEncoder extends GenericHeicEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see EncoderInterface::function()
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $result = $image->core()->native()->writeToBuffer('.heic', [
            'Q' => $this->quality,
        ]);

        return new EncodedImage($result, 'image/heic');
    }
}
