<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\DrawPolygonModifier as GenericDrawPolygonModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Exception as VipsException;

class DrawPolygonModifier extends GenericDrawPolygonModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws VipsException|RuntimeException|\RuntimeException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $polygon = Driver::createShape(
            'polygon',
            [
                'fill' => $this->backgroundColor()->toString(),
                'stroke' => $this->borderColor()->toString(),
                'stroke-width' => $this->drawable->borderSize(),
                'points' => implode(' ', array_map(
                    fn(array $coordinates): string => implode(',', $coordinates),
                    array_chunk($this->drawable->toArray(), 2),
                )),
            ],
            $image->width(),
            $image->height(),
        );

        $frames = [];
        foreach ($image as $frame) {
            $frames[] = $frame->setNative(
                $frame->native()->composite($polygon, [BlendMode::OVER])
            );
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }
}
