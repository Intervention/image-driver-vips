<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Encoders;

use Intervention\Image\EncodedImage;
use Intervention\Image\Encoders\WebpEncoder as GenericWebpEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Config as VipsConfig;
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
        $result = $image->core()->native()->writeToBuffer('.webp', $this->getOptions());

        return new EncodedImage($result, 'image/webp');
    }

    /**
     * @return array{lossless: bool, Q: int, keep?: int, strip?: bool}
     */
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
            $options['strip'] = $strip;
        }

        return $options;
    }
}
