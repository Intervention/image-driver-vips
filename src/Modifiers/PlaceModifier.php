<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Geometry\Rectangle;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\PointInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\PlaceModifier as GenericPlaceModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Extend;

class PlaceModifier extends GenericPlaceModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see ModifierInterface::apply()
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

        $this->placeWatermark($watermarkNative, $position, $image);

        return $image;
    }

    /**
     * @throws RuntimeException
     */
    private function placeWatermark(
        mixed $watermarkNative,
        PointInterface $position,
        ImageInterface $image
    ): void {
        if ($watermarkNative->hasAlpha()) {
            /** @var Rectangle $size */
            $size = $image->size();
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

            $image->core()->setNative(
                $image->core()->native()->composite2(
                    $watermarkNative,
                    BlendMode::OVER
                )
            );
        } else {
            $image->core()->setNative(
                $image->core()->native()->insert(
                    $watermarkNative->bandjoin_const(255),
                    $position->x(),
                    $position->y()
                )
            );
        }
    }
}
