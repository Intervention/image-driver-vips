<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ResizeCanvasRelativeModifier as GenericResizeCanvasRelativeModifier;

class ResizeCanvasRelativeModifier extends GenericResizeCanvasRelativeModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $cropSize = $this->cropSize($image, true);

        $image->modify(new CropModifier(
            $cropSize->width(),
            $cropSize->height(),
            $cropSize->pivot()->x(),
            $cropSize->pivot()->y(),
            $this->background,
        ));

        return $image;
    }
}
