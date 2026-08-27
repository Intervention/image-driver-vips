<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\RemoveProfileModifier as GenericRemoveProfileModifier;
use Jcupitt\Vips\Exception as VipsException;

class RemoveProfileModifier extends GenericRemoveProfileModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws ModifierException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $core = $image->core();

        // an image without a profile is left alone. libvips' remove() throws on
        // a field that is not there, so this is what keeps the call below safe
        if (!in_array('icc-profile-data', $core->native()->getFields())) {
            return $image;
        }

        try {
            // work on a copy, the vips image is shared with any clone of the
            // image and removing the field in place would strip both
            $native = $core->native()->copy();
            $native->remove('icc-profile-data');
        } catch (VipsException $e) {
            throw new ModifierException('Failed to remove profile from image', previous: $e);
        }

        // this also clears the stashed source, which matters. Left in place, a
        // later resize would reload the original file and bring the profile
        // back. It costs the shrink on load path though, so removing the
        // profile before a resize is markedly slower than doing it after.
        $core->setNative($native);

        return $image;
    }
}
