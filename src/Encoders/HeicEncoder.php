<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\HeicEncoder as GenericHeicEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\ForeignKeep;

class HeicEncoder extends GenericHeicEncoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\EncoderInterface::encode()
     */
    public function encode(ImageInterface $image): EncodedImage
    {
        $keep = $this->strip || (is_null($this->strip) &&
            $this->driver()->config()->strip) ? ForeignKeep::ICC : ForeignKeep::ALL;

        $result = $image->core()->native()->writeToBuffer('.heic', [
            'Q' => $this->quality,
            'keep' => $keep,
        ]);

        return new EncodedImage($result, 'image/heic');
    }
}
