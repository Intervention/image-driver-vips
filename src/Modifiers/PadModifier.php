<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Jcupitt\Vips\Extend;

class PadModifier extends ContainModifier
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $resize = $this->getResizeSize($image);
        $bgColor = $this->driver()->handleInput($this->background);

        if (!$image->isAnimated()) {
            $contained = $this->pad($image->core()->first(), $resize, $bgColor)->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->pad($frame, $resize, $bgColor);
            }

            $contained = Core::replaceFrames($image->core()->native(), $frames);
        }

        $image->core()->setNative($contained);

        return $image;
    }

    /**
     * Apply padded image resizing
     *
     * @throws ColorException
     */
    private function pad(FrameInterface $frame, SizeInterface $resize, ColorInterface $bgColor): FrameInterface
    {
        $cropWidth = min($frame->native()->width, $resize->width());
        $cropHeight = min($frame->native()->height, $resize->height());

        $resized = $frame->native()->thumbnail_image($cropWidth, [
            'height' => $cropHeight,
            'no_rotate' => true,
        ]);

        if (!$resized->hasAlpha()) {
            if ($resized->bands === 1) {
                // Grayscale -> RGB
                $resized = $resized->colourspace('srgb');
            }
            $resized = $resized->bandjoin_const(255);
        }

        $frame->setNative(
            $resized->gravity(
                $this->positionToGravity($this->position),
                $resize->width(),
                $resize->height(),
                [
                    'extend' => Extend::BACKGROUND,
                    'background' => $bgColor->toArray(),
                ]
            )
        );

        return $frame;
    }
}
