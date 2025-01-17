<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ColorizeModifier as GenericColorizeModifier;

class ColorizeModifier extends GenericColorizeModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $bands = $image->core()->native()->bands;

        $image->core()->setNative(
            $image->core()->native()->linear(
                array_fill(0, $bands, 1),
                array_pad(array_map(fn(int $value): int => $value * 3, [
                    $this->red,
                    $this->green,
                    $this->blue,
                ]), $bands, 0)
            )
        );

        return $image;
    }
}
