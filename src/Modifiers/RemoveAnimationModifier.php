<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\RemoveAnimationModifier as GenericRemoveAnimationModifier;

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

        return $image->sliceAnimation(parent::normalizePosition($image), 1);
    }
}
