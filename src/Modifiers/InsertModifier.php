<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\PointInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\InsertModifier as GenericInsertModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;

class InsertModifier extends GenericInsertModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws StateException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $watermark = $this->driver()->decodeImage($this->image);
        $elementNative = $watermark->core()->native();
        $position = $this->position($image, $watermark);

        if ($this->opacity < 100) {
            if (!$elementNative->hasAlpha()) {
                $elementNative = $elementNative->bandjoin_const(255);
            }

            $elementNative = $elementNative->multiply([
                1.0,
                1.0,
                1.0,
                $this->opacity / 100,
            ]);
        }

        if (!$image->isAnimated()) {
            $watermarked = $this->placeElement(
                $elementNative,
                $position,
                $image->core()->first(),
            )->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->placeElement(
                    $elementNative,
                    $position,
                    $frame,
                );
            }

            $watermarked = Core::replaceFrames($image->core()->native(), $frames);
        }

        $image->core()->setNative($watermarked);

        return $image;
    }

    /**
     * Place element at position on frame.
     */
    private function placeElement(
        VipsImage $element,
        PointInterface $position,
        FrameInterface $frame
    ): FrameInterface {
        if ($element->hasAlpha()) {
            $element = $element->embed(
                $position->x(),
                $position->y(),
                $frame->size()->width(),
                $frame->size()->height(),
                [
                    'extend' => Extend::BACKGROUND,
                    'background' => [0, 0, 0, 0],
                ]
            );

            $frame->setNative(
                $frame->native()->composite2(
                    $element,
                    BlendMode::OVER
                )
            );
        } else {
            $frame->setNative(
                $frame->native()->insert(
                    $element,
                    $position->x(),
                    $position->y()
                )
            );
        }

        return $frame;
    }
}
