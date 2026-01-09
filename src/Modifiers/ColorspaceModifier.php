<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ColorspaceModifier as GenericColorspaceModifier;
use Jcupitt\Vips\Exception as VipsException;

class ColorspaceModifier extends GenericColorspaceModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws ModifierException
     * @throws NotSupportedException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        try {
            $native = $image->core()->native()->copy([
                'interpretation' => ColorProcessor::colorspaceToInterpretation(
                    $this->targetColorspace()
                )
            ]);
        } catch (VipsException $e) {
            throw new ModifierException('Failed to modify image colorspace', previous: $e);
        }

        $image->core()->setNative($native);

        return $image;
    }
}
