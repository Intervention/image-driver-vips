<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\Jpeg2000Encoder as GenericJpeg2000Encoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\ForeignKeep;

class Jpeg2000Encoder extends GenericJpeg2000Encoder implements SpecializedInterface
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

        $keep = $this->strip || (is_null($this->strip) &&
            $this->driver()->config()->strip) ? ForeignKeep::ICC : ForeignKeep::ALL;

        $result = $vipsImage->writeToBuffer('.j2k', [
            'lossless' => $this->quality === 100,
            'Q' => $this->quality,
            'keep' => $keep,
        ]);

        return new EncodedImage($result, 'image/jp2');
    }
}
