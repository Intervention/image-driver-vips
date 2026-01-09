<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\BrightnessModifier as GenericBrightnessModifier;
use Jcupitt\Vips\Exception as VipsException;

class BrightnessModifier extends GenericBrightnessModifier implements SpecializedInterface
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
        if ($image->core()->native()->hasAlpha()) {
            try {
                $flatten = $image->core()->native()->extract_band(
                    0,
                    ['n' => $image->core()->native()->bands - 1],
                );

                $mask = $image->core()->native()->extract_band(
                    $image->core()->native()->bands - 1,
                    ['n' => 1],
                );

                $brightened = $flatten
                    ->linear(1, $this->level)
                    ->bandjoin($mask)
                    ->cast($image->core()->native()->format);
            } catch (VipsException $e) {
                throw new ModifierException('Failed to change image brightness', previous: $e);
            }
        } else {
            try {
                $brightened = $image->core()->native()
                    ->linear(1, $this->level)
                    ->cast($image->core()->native()->format);
            } catch (VipsException $e) {
                throw new ModifierException('Failed to change image brightness', previous: $e);
            }
        }

        $image->core()->setNative($brightened);

        return $image;
    }
}
