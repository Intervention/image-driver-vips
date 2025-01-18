<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Collection;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\ForeignKeep;
use Jcupitt\Vips\Image as VipsImage;

class StripMetaModifier implements ModifierInterface, SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws VipsException
     *
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $buf = $image->core()->native()->tiffsave_buffer([
            'keep' => ForeignKeep::ICC,
        ]);

        $image->setExif(new Collection());
        $image->core()->setNative(
            VipsImage::newFromBuffer($buf)
        );

        return $image;
    }
}
