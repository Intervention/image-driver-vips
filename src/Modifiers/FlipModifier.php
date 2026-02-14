<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Direction;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\FlipModifier as GenericFlipModifier;
use Jcupitt\Vips\Direction as VipsDirection;
use Jcupitt\Vips\Exception as VipsException;

class FlipModifier extends GenericFlipModifier implements SpecializedInterface
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
        Core::ensureInMemory($image->core());

        $direction = $this->direction === Direction::HORIZONTAL ? VipsDirection::HORIZONTAL : VipsDirection::VERTICAL;

        try {
            $native = $image->core()->native()->flip($direction);
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to flip image vertically',
                previous: $e
            );
        }

        $image->core()->setNative($native);

        return $image;
    }
}
