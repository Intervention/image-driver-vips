<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\HeicEncoder as GenericHeicEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Config as VipsConfig;
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
        $result = $image->core()->native()->writeToBuffer('.heic', $this->getOptions());

        return new EncodedImage($result, 'image/heic');
    }

    protected function getOptions(): array
    {
        $options = [
            'lossless' => $this->quality === 100,
            'Q' => $this->quality,
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
