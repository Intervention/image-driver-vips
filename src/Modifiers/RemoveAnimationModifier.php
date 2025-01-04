<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\AnimationException;
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
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        if (!$image->isAnimated()) {
            return $image;
        }

        $position = parent::normalizePosition($image);
        $page_height = $image->core()->native()->get('page-height');

        try {
            $modified = $image->core()->native()->crop(
                0,
                $position * $page_height,
                $image->width(),
                $page_height,
            );
            $modified->set('n-pages', 1);
        } catch (VipsException) {
            throw new AnimationException('Frame #' . $position . ' could not be found in the image.');
        }

        $image->core()->setNative($modified);

        return $image;
    }
}
