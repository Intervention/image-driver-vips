<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Geometry\Rectangle;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\PointInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\PlaceModifier as GenericPlaceModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Extend;

class PlaceModifier extends GenericPlaceModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see ModifierInterface::apply()
     * @throws RuntimeException|VipsException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $watermark = $this->driver()->handleInput($this->element);
        $watermarkNative = $watermark->core()->native();
        $position = $this->getPosition($image, $watermark);

        if ($this->opacity < 100) {
            if (!$watermarkNative->hasAlpha()) {
                $watermarkNative = $watermarkNative->bandjoin_const(255);
            }

            $watermarkNative = $watermarkNative->multiply([
                1.0,
                1.0,
                1.0,
                $this->opacity / 100,
            ]);
        }

        if (!$image->isAnimated()) {
            $watermarked = $this->placeWatermark($watermarkNative, $position, $image->core()->first())->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->placeWatermark($watermarkNative, $position, $frame);
            }

            $watermarked = Core::replaceFrames($image->core()->native(), $frames);
        }

        $image->core()->setNative($watermarked);

        return $image;
    }

    /**
     * @throws RuntimeException
     */
    private function placeWatermark(
        mixed $watermarkNative,
        PointInterface $position,
        FrameInterface $frame
    ): FrameInterface {
        if ($watermarkNative->hasAlpha()) {
            /** @var Rectangle $size */
            $size = $frame->size();
            $imageSize = $size->align($this->position);

            $watermarkNative = $watermarkNative->embed(
                $position->x(),
                $position->y(),
                $imageSize->width(),
                $imageSize->height(),
                [
                    'extend' => Extend::BACKGROUND,
                    'background' => [0, 0, 0, 0],
                ]
            );

            $frame->setNative(
                $frame->native()->composite2(
                    $watermarkNative,
                    BlendMode::OVER
                )
            );
        } else {
            $frame->setNative(
                $frame->native()->insert(
                    $watermarkNative->bandjoin_const(255),
                    $position->x(),
                    $position->y()
                )
            );
        }

        return $frame;
    }
}
