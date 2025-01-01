<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\CoverModifier as GenericCoverModifier;

class CoverModifier extends GenericCoverModifier implements SpecializedInterface
{
    public function apply(ImageInterface $image): ImageInterface
    {
        $crop = $this->getCropSize($image);
        $resize = $this->getResizeSize($crop);

        $image->core()->setNative(
            $image->core()->native()
                ->crop(
                    $crop->pivot()->x(),
                    $crop->pivot()->y(),
                    $crop->width(),
                    $crop->height()
                )
                ->thumbnail_image($resize->width(), [
                    'height' => $resize->height(),
                    'size' => 'force',
                    'no_rotate' => true,
                ])
        );

        return $image;
    }
}
