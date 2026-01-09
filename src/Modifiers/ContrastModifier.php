<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ContrastModifier as GenericContrastModifier;
use Jcupitt\Vips\Exception as VipsException;

class ContrastModifier extends GenericContrastModifier implements SpecializedInterface
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
        // calculate a and b for linear
        $a = 1 + $this->level / 100;
        $b = 255 * (1 - $a);

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
                    previous: $e
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
                    previous: $e
                );
            }
        }

        $image->core()->setNative($brightened);

        return $image;
    }
}
