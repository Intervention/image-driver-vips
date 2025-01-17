<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\BrightnessModifier as GenericBrightnessModifier;

class BrightnessModifier extends GenericBrightnessModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        if ($image->core()->native()->hasAlpha()) {
            $flatten = $image->core()->native()->extract_band(0, ['n' => $image->core()->native()->bands - 1]);
            $mask = $image->core()->native()->extract_band($image->core()->native()->bands - 1, ['n' => 1]);

            $brightened = $flatten
                ->linear([1, 1, 1], [$this->level, $this->level, $this->level])
                ->bandjoin($mask)
                ->cast($image->core()->native()->format)
            ;
        } else {
            $brightened = $image->core()->native()
                ->linear([1, 1, 1], [$this->level, $this->level, $this->level])
                ->cast($image->core()->native()->format)
            ;
        }

        $image->core()->setNative($brightened);

        return $image;
    }
}
