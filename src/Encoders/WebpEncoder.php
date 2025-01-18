<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\WebpEncoder as GenericWebpEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\ForeignKeep;

class WebpEncoder extends GenericWebpEncoder implements SpecializedInterface
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

        $result = $image->core()->native()->writeToBuffer('.webp', [
            'lossless' => $this->quality === 100,
            'Q' => $this->quality,
            'keep' => $keep,
        ]);

        return new EncodedImage($result, 'image/webp');
    }
}
