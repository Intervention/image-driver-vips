<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\InvertModifier as GenericInvertModifier;

class InvertModifier extends GenericInvertModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $vipsImage = $image->core()->native();
        $hasAlpha = $vipsImage->hasAlpha();

        // don't invert alpha channel
        if ($hasAlpha) {
            $alpha = $vipsImage->extract_band($vipsImage->bands - 1, ['n' => 1]);
            $vipsImage = $vipsImage->extract_band(0, ['n' => $vipsImage->bands - 1]);
        }

        // invert colors
        $vipsImage = $vipsImage->invert();

        // re-apply alpha channel
        if ($hasAlpha) {
            $vipsImage = $vipsImage->bandjoin($alpha);
        }

        $image->core()->setNative($vipsImage);

        return $image;
    }
}
