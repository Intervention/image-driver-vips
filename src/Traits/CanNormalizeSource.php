<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Traits;

use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

trait CanNormalizeSource
{
    /**
     * Bring a vips image into the shape the driver works with: grayscale
     * (B_W, GREY16) is converted to sRGB, and 3-band sRGB gets an alpha
     * band. NativeObjectDecoder applies this to every loaded source. Whoever
     * reopens a stashed source, Core::__clone() and the thumbnail fast path
     * of the resize modifiers, replays it so the result matches the decoded
     * image, and ColorspaceModifier applies it after a conversion.
     *
     * @throws VipsException
     */
    private function normalizeSource(VipsImage $image): VipsImage
    {
        if (in_array($image->interpretation, [Interpretation::B_W, Interpretation::GREY16], true)) {
            $image = $image->icc_transform(Interpretation::SRGB);
        }

        if ($image->interpretation === Interpretation::SRGB && $image->bands === 3) {
            $image = $image->bandjoin_const(255);
        }

        return $image;
    }
}
