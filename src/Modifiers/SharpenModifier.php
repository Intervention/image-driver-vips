<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\SharpenModifier as GenericSharpenModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;

class SharpenModifier extends GenericSharpenModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws ModifierException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        try {
            $native = $image->core()->native()->conv($this->unsharpMask());
        } catch (VipsException $e) {
            throw new ModifierException('Failed to sharpen image', previous: $e);
        }

        $image->core()->setNative($native);

        return $image;
    }

    /**
     * Generate unsharp mask
     *
     * @throws VipsException
     */
    private function unsharpMask(): VipsImage
    {
        $min = $this->level >= 10 ? $this->level * -0.01 : 0;
        $max = $this->level * -0.025;
        $abs = ((4 * $min + 4 * $max) * -1) + 1;

        return VipsImage::newFromArray([
            [$min, $max, $min],
            [$max, $abs, $max],
            [$min, $max, $min],
        ], 1, 0);
    }
}
