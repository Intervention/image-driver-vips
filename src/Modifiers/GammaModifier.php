<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\GammaModifier as GenericGammaModifier;

class GammaModifier extends GenericGammaModifier implements SpecializedInterface
{
    public function apply(ImageInterface $image): ImageInterface
    {
        $image->core()->setNative(
            $image->core()->native()->gamma([
                'exponent' => $this->gamma
            ])
        );

        return $image;
    }
}
