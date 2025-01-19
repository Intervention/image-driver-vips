<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Collection;
use Intervention\Image\Exceptions\AnimationException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Config as VipsConfig;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\ForeignKeep;
use Jcupitt\Vips\Image as VipsImage;

class StripMetaModifier implements ModifierInterface, SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws VipsException|AnimationException
     *
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $options = VipsConfig::atLeast(8, 15) ? [
            'keep' => ForeignKeep::ICC,
        ] : [
            'strip' => true,
        ];

        $buf = $image->core()->native()->tiffsave_buffer($options);

        $image->setExif(new Collection());
        $image->core()->setNative(
            VipsImage::newFromBuffer($buf)
        );

        return $image;
    }
}
