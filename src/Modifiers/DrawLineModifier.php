<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Exceptions\RuntimeException;
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
     * @see ModifierInterface::apply()
     * @throws VipsException|RuntimeException|\RuntimeException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $ellipse = Driver::createShape(
            'line',
            [
                'x1' => $this->drawable->start()->x(),
                'y1' => $this->drawable->start()->y(),
                'x2' => $this->drawable->end()->x(),
                'y2' => $this->drawable->end()->y(),
                'stroke' => $this->backgroundColor()->toString(),
                'stroke-width' => $this->drawable->width(),
            ],
            $image->width(),
            $image->height(),
        );

        $frames = [];
        foreach ($image as $frame) {
            $frames[] = $frame->setNative(
                $frame->native()->composite($ellipse->core()->native(), [BlendMode::OVER])
            );
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }
}
