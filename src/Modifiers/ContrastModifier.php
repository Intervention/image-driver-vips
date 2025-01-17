<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ContrastModifier as GenericContrastModifier;

class ContrastModifier extends GenericContrastModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // calculate a and b for linear
        $a = 1 + $this->level / 100;
        $b = 255 * (1 - $a);

        if ($image->core()->native()->hasAlpha()) {
            $flatten = $image->core()->native()->extract_band(0, ['n' => $image->core()->native()->bands - 1]);
            $mask = $image->core()->native()->extract_band($image->core()->native()->bands - 1, ['n' => 1]);

            $brightened = $flatten
                ->linear($a, $b)
                ->bandjoin($mask)
                ->cast($image->core()->native()->format)
            ;
        } else {
            $brightened = $image->core()->native()
                ->linear($a, $b)
                ->cast($image->core()->native()->format)
            ;
        }

        $image->core()->setNative($brightened);

        return $image;
    }
}
