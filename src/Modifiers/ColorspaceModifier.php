<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Drivers\Vips\Traits\CanNormalizeBands;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ColorspaceModifier as GenericColorspaceModifier;
use Jcupitt\Vips\Exception as VipsException;

class ColorspaceModifier extends GenericColorspaceModifier implements SpecializedInterface
{
    use CanNormalizeBands;

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws ModifierException
     * @throws NotSupportedException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $interpretation = ColorProcessor::colorspaceToInterpretation($this->targetColorspace());

        try {
            // colourspace() converts the pixel data, unlike copy() with a new
            // interpretation, which only re-labels the bands and leaves the
            // image itself untouched. Converting a cmyk source drops its
            // fourth band, so the alpha band has to be restored afterwards.
            $native = $this->normalizeBands(
                $image->core()->native()->colourspace($interpretation),
            );
        } catch (VipsException $e) {
            throw new ModifierException('Failed to modify image colorspace', previous: $e);
        }

        $image->core()->setNative($native);

        return $image;
    }
}
