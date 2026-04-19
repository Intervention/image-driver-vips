<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Colors\Rgb\Colorspace as Rgb;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
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
     * @throws StateException
     * @throws ModifierException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $xmlAttributes = [
            'fill' => 'rgba(0, 0, 0, 0)',
            'points' => implode(' ', array_map(
                fn(array $coordinates): string => implode(',', $coordinates),
                array_chunk($this->drawable->toArray(), 2),
            )),
        ];

        if ($this->drawable->hasBackgroundColor()) {
            $xmlAttributes['fill'] = $this->backgroundColor()->toColorspace(Rgb::class)->toString();
        }

        if ($this->drawable->hasBorder()) {
            $xmlAttributes['stroke'] = $this->borderColor()->toColorspace(Rgb::class)->toString();
            $xmlAttributes['stroke-width'] = $this->drawable->borderSize();
        }

        $polygon = Driver::createShape(
            'polygon',
            $xmlAttributes,
            $image->width(),
            $image->height(),
        );

        $frames = [];
        foreach ($image as $frame) {
            try {
                $native = $frame->native()->composite($polygon, [BlendMode::OVER]);
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to draw polygon',
                    previous: $e
                );
            }

            $frames[] = $frame->setNative($native);
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }
}
