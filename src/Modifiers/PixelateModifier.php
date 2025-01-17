<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\PixelateModifier as GenericPixelateModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Kernel;

class PixelateModifier extends GenericPixelateModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     * @throws RuntimeException|VipsException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        if (!$image->isAnimated()) {
            $pixelated = $this->pixelate($image->core()->first())->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->pixelate($frame);
            }

            $pixelated = Core::replaceFrames($image->core()->native(), $frames);
        }

        $image->core()->setNative($pixelated);

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
