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
use Jcupitt\Vips\Image as VipsImage;

class PlaceModifier extends GenericPlaceModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     * @throws RuntimeException|VipsException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $element = $this->driver()->handleInput($this->element);
        $elementNative = $element->core()->native();
        $position = $this->getPosition($image, $element);

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
            $watermarked = $this->placeElement($elementNative, $position, $image->core()->first())->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->placeElement($elementNative, $position, $frame);
            }

            $watermarked = Core::replaceFrames($image->core()->native(), $frames);
        }

        $image->core()->setNative($watermarked);

        return $image;
    }

    /**
     * @throws RuntimeException
     */
    private function placeElement(
        VipsImage $elementNative,
        PointInterface $position,
        FrameInterface $frame
    ): FrameInterface {
        if ($elementNative->hasAlpha()) {
            /** @var Rectangle $size */
            $size = $frame->size();
            $imageSize = $size->align($this->position);

            $elementNative = $elementNative->embed(
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
                    $elementNative,
                    BlendMode::OVER
                )
            );
        } else {
            $frame->setNative(
                $frame->native()->insert(
                    $elementNative->bandjoin_const(255),
                    $position->x(),
                    $position->y()
                )
            );
        }

        return $frame;
    }
}
