<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Traits;

use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

trait CanNormalizeBands
{
    /**
     * Re-apply the SRGB-3-band -> 4-band bandjoin that NativeObjectDecoder
     * applies on first decode, so the resized image's bandcount matches
     * what the rest of the pipeline expects.
     *
     * @throws VipsException
     */
    private function normalizeBands(VipsImage $image): VipsImage
    {
        if ($image->interpretation === Interpretation::SRGB && $image->bands === 3) {
            return $image->bandjoin_const(255);
        }

        return $image;
    }
}
