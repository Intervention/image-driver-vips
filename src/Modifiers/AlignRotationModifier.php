<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\AlignRotationModifier as GenericAlignRotationModifier;

class AlignRotationModifier extends GenericAlignRotationModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $image->core()->setNative(
            // autorot() does not seem to work with the default sequential access of this library
            Core::ensureInMemory($image->core())->native()->autorot()
        );

        return $image;
    }
}
