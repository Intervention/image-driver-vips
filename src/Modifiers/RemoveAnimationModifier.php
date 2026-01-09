<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\RemoveAnimationModifier as GenericRemoveAnimationModifier;
use Jcupitt\Vips\Exception as VipsException;

class RemoveAnimationModifier extends GenericRemoveAnimationModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws InvalidArgumentException
     * @throws ModifierException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        if (!$image->isAnimated()) {
            return $image;
        }

        $position = parent::normalizePosition($image);
        $pageHeight = $image->core()->native()->get('page-height');

        try {
            $modified = $image->core()->native()->crop(
                0,
                $position * $pageHeight,
                $image->width(),
                $pageHeight,
            );
            $modified->set('n-pages', 1);
        } catch (VipsException) {
            throw new ModifierException('Frame #' . $position . ' could not be found in the image.');
        }

        $image->core()->setNative($modified);

        return $image;
    }
}
