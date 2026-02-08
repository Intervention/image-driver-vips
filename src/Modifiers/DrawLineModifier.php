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
use Intervention\Image\Modifiers\DrawLineModifier as GenericDrawLineModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Exception as VipsException;

class DrawLineModifier extends GenericDrawLineModifier implements SpecializedInterface
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
            'x1' => $this->drawable->start()->x(),
            'y1' => $this->drawable->start()->y(),
            'x2' => $this->drawable->end()->x(),
            'y2' => $this->drawable->end()->y(),
        ];

        if ($this->drawable->hasBackgroundColor()) {
            $xmlAttributes['stroke'] = $this->backgroundColor()->toColorspace(Rgb::class)->toString();
            $xmlAttributes['stroke-width'] = $this->drawable->width();
        }

        $line = Driver::createShape('line', $xmlAttributes, $image->width(), $image->height());

        $frames = [];
        foreach ($image as $frame) {
            try {
                $native = $frame->native()->composite($line, [BlendMode::OVER]);
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to draw line',
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
