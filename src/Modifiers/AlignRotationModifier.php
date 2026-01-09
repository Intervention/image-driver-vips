<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\AlignRotationModifier as GenericAlignRotationModifier;
use Jcupitt\Vips\Exception as VipsException;

class AlignRotationModifier extends GenericAlignRotationModifier implements SpecializedInterface
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
            // autorot() does not seem to work with the default sequential access of this library
            $native = Core::ensureInMemory($image->core())->native()->autorot();
        } catch (VipsException $e) {
            throw new ModifierException('Failed to align image rotation', previous: $e);
        }

        $image->core()->setNative($native);

        return $image;
    }
}
