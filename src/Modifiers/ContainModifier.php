<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Traits\PositionToGravity;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ContainModifier as GenericContainModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Extend;

class ContainModifier extends GenericContainModifier implements SpecializedInterface
{
    use PositionToGravity;

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException|VipsException
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $resize = $this->getResizeSize($image);
        $bgColor = $this->driver()->handleInput($this->background);

        if (!$image->isAnimated()) {
            $contained = $this->contain($image->core()->first(), $resize, $bgColor)->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->contain($frame, $resize, $bgColor);
            }

            $contained = Core::replaceFrames($image->core()->native(), $frames);
        }

        $image->core()->setNative($contained);

        return $image;
    }

    /**
     * @throws ColorException
     */
    private function contain(FrameInterface $frame, SizeInterface $resize, ColorInterface $bgColor): FrameInterface
    {
        $resized = $frame->native()->thumbnail_image($resize->width(), [
            'height' => $resize->height(),
            'no_rotate' => true,
        ]);

        if (!$resized->hasAlpha()) {
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
