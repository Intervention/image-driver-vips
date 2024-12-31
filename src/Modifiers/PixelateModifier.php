<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\PixelateModifier as GenericPixelateModifier;
use Jcupitt\Vips\Kernel;

class PixelateModifier extends GenericPixelateModifier implements SpecializedInterface
{
    public function apply(ImageInterface $image): ImageInterface
    {
        if (!$image->isAnimated()) {
            $image->core()->setNative(
                $this->pixelate($image->core()->first())->native()
            );
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->pixelate($frame);
            }

            $image->core()->setNative(
                Core::createFromFrames($frames)->native()
            );
        }
        $image->core()->setNative(
            $image->core()->native()
                ->resize(1 / $this->size)
                ->resize($this->size, ['kernel' => Kernel::NEAREST])
                ->crop(0, 0, $image->width(), $image->height())
        );

        return $image;
    }

    private function pixelate(FrameInterface $frame): FrameInterface
    {
        $frame->setNative(
            $frame->native()
                ->resize(1 / $this->size)
                ->resize($this->size, ['kernel' => Kernel::NEAREST])
                ->crop(0, 0, $frame->size()->width(), $frame->size()->height())
        );

        return $frame;
    }
}
