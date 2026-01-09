<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\GrayscaleModifier as GenericGrayscaleModifier;
use Jcupitt\Vips\Interpretation;

class GrayscaleModifier extends GenericGrayscaleModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // turn image to grayscale
        $image->core()->setNative(
            $image->core()->native()->colourspace(Interpretation::B_W)
        );

        // return to srgb colorspace with grayscale image
        $image->core()->setNative(
            $image->core()->native()->colourspace(Interpretation::SRGB)
        );

        return $image;
    }
}
