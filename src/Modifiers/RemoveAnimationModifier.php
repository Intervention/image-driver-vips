<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\AnimationException;
use Intervention\Image\Exceptions\InputException;
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

        $position = $this->normalizedPosition($image);
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

    /**
     * Normalize modifier's position to integer
     *
     * TODO: Remove this method and use the version from parent class in newer
     * version of dependency "Intervention Image".
     *
     * @param ImageInterface $image
     * @throws InputException
     * @return int
     */
    private function normalizedPosition(ImageInterface $image): int
    {
        if (is_int($this->position)) {
            return $this->position;
        }

        if (is_numeric($this->position)) {
            return (int) $this->position;
        }

        if (preg_match("/^(?P<percent>[0-9]{1,3})%$/", $this->position, $matches) != 1) {
            throw new InputException(
                'Position must be either integer or a percent value as string.'
            );
        }

        $total = count($image);
        $position = intval(round($total / 100 * intval($matches['percent'])));

        return $position == $total ? $position - 1 : $position;
    }
}
