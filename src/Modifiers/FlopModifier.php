<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\FlopModifier as GenericFlopModifier;
use Jcupitt\Vips\Direction;

class FlopModifier extends GenericFlopModifier implements SpecializedInterface
{
    public function apply(ImageInterface $image): ImageInterface
    {
        $image->core()->setNative(
            $image->core()->native()->flip(Direction::HORIZONTAL)
        );

        return $image;
    }
}
