<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ColorspaceModifier as GenericColorspaceModifier;

class ColorspaceModifier extends GenericColorspaceModifier implements SpecializedInterface
{
    public function apply(ImageInterface $image): ImageInterface
    {
        $image->core()->setNative(
            $image->core()->native()->copy([
                'interpretation' => ColorProcessor::colorspaceToInterpretation(
                    $this->targetColorspace()
                )
            ])
        );

        return $image;
    }
}
