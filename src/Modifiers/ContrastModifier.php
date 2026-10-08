<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ContrastModifier as GenericContrastModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

class ContrastModifier extends GenericContrastModifier implements SpecializedInterface
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
        // calculate a and b for linear, pivoting on mid grey so that it stays
        // unchanged while the other tones are pushed away from it or pulled
        // towards it
        $a = 1 + $this->level / 100;
        $b = $this->midGrey($image->core()->native()) * (1 - $a);

        if ($image->core()->native()->hasAlpha()) {
            try {
                $flatten = $image->core()->native()->extract_band(0, ['n' => $image->core()->native()->bands - 1]);
                $mask = $image->core()->native()->extract_band($image->core()->native()->bands - 1, ['n' => 1]);

                $brightened = $flatten
                    ->linear($a, $b)
                    ->bandjoin($mask)
                    ->cast($image->core()->native()->format);
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to process contrast adjustment',
                    previous: $e,
                );
            }
        } else {
            try {
                $brightened = $image->core()->native()
                    ->linear($a, $b)
                    ->cast($image->core()->native()->format);
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to process contrast adjustment',
                    previous: $e,
                );
            }
        }

        $image->core()->setNative($brightened);

        return $image;
    }

    /**
     * Return mid grey on the value range that goes with the interpretation of
     * the given image: 0-65535 for 16-bit sources, 0-1 for scRGB ones and
     * 0-255 for all others.
     */
    private function midGrey(VipsImage $vipsImage): float
    {
        return match ($vipsImage->interpretation) {
            Interpretation::RGB16, Interpretation::GREY16 => 65535,
            Interpretation::SCRGB => 1,
            default => 255,
        } / 2;
    }
}
