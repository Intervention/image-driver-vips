<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\FlipModifier as GenericFlipModifier;
use Jcupitt\Vips\Direction;
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
        try {
            $native = $image->core()->native()->flip(Direction::VERTICAL);
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
