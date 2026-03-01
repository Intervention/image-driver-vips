<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\BlurModifier as GenericBlurModifier;
use Jcupitt\Vips\Exception as VipsException;

class BlurModifier extends GenericBlurModifier implements SpecializedInterface
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
            $native = $image->core()->native()->gaussblur($this->level * 0.53);
        } catch (VipsException $e) {
            throw new ModifierException('Failed to apply image blur', previous: $e);
        }

        $image->core()->setNative($native);

        return $image;
    }
}
