<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\GrayscaleModifier as GenericGrayscaleModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Interpretation;

class GrayscaleModifier extends GenericGrayscaleModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws DriverException
     * @throws ModifierException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // grayscale is a color operation and is not meant to move the image to
        // another colorspace, so the b-w detour has to end back where it
        // started
        $interpretation = $image->core()->native()->interpretation;

        try {
            $grayscale = $image->core()->native()->colourspace(Interpretation::B_W);

            try {
                $native = $grayscale->colourspace($interpretation);
            } catch (VipsException) {
                // colourspace() takes its target verbatim. Interpretations
                // like multiband, matrix or rgb are tags rather than color
                // spaces and it has no route back to them, so those images
                // stay in srgb the way they did before.
                $native = $grayscale->colourspace(Interpretation::SRGB);
            }
        } catch (VipsException $e) {
            throw new ModifierException('Failed to modify image to grayscale', previous: $e);
        }

        // the whole conversion is committed at once, a failed restore must not
        // leave the caller holding the b-w intermediate
        $image->core()->setNative($native);

        return $image;
    }
}
