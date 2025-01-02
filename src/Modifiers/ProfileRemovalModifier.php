<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ProfileRemovalModifier as GenericProfileRemovalModifier;
use Jcupitt\Vips\Exception as VipsException;

class ProfileRemovalModifier extends GenericProfileRemovalModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        try {
            $image->core()->native()->remove('icc-profile-data');
        } catch (VipsException) {
            //
        }

        return $image;
    }
}
