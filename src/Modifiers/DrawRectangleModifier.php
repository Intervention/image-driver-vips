<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\DrawRectangleModifier as GenericDrawRectangleModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Exception as VipsException;

class DrawRectangleModifier extends GenericDrawRectangleModifier implements SpecializedInterface
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
        $xmlAttributes = [
            'x' => $this->drawable->position()->x(),
            'y' => $this->drawable->position()->y(),
            'width' => $this->drawable->width(),
            'height' => $this->drawable->height(),
            'fill' => $this->backgroundColor()->toString(),
        ];

        if ($this->drawable->hasBorder()) {
            $xmlAttributes['stroke'] = $this->borderColor()->toString();
            $xmlAttributes['stroke-width'] = $this->drawable->borderSize();
        }

        $rectangle = Driver::createShape('rect', $xmlAttributes, $image->width(), $image->height());

        $frames = [];
        foreach ($image as $frame) {
            $frames[] = $frame->setNative(
                $frame->native()->composite($rectangle, [BlendMode::OVER])
            );
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }
}
