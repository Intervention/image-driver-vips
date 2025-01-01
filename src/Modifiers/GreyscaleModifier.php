<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\GreyscaleModifier as GenericGreyscaleModifier;

class GreyscaleModifier extends GenericGreyscaleModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // turn image to black & white
        $image->core()->setNative(
            $image->core()->native()->colourspace('b-w')
        );

        // return to srgb colorspace with b/w image
        $image->core()->setNative(
            $image->core()->native()->colourspace('srgb')
        );

        return $image;
    }
}
