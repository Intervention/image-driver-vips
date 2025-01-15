<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\CoverModifier as GenericCoverModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;

class CoverModifier extends GenericCoverModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException|VipsException
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $crop = $this->getCropSize($image);
        $resize = $this->getResizeSize($crop);

        $frames = [];
        foreach ($image as $frame) {
            $frames[] = $frame->setNative($this->cropResizeFrame($frame, $crop, $resize));
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }

    private function cropResizeFrame(
        FrameInterface $frame,
        SizeInterface $cropSize,
        SizeInterface $resizeSize
    ): VipsImage {
        return $frame->native()->crop(
            $cropSize->pivot()->x(),
            $cropSize->pivot()->y(),
            $cropSize->width(),
            $cropSize->height()
        )->thumbnail_image($resizeSize->width(), [
            'height' => $resizeSize->height(),
            'size' => 'force',
            'no_rotate' => true,
        ]) ;
    }
}
