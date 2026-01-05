<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\DrawEllipseModifier as GenericDrawEllipseModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Exception as VipsException;

class DrawEllipseModifier extends GenericDrawEllipseModifier implements SpecializedInterface
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
            'cx' => $this->drawable->position()->x(),
            'cy' => $this->drawable->position()->y(),
            'rx' => $this->drawable->width() / 2,
            'ry' => $this->drawable->height() / 2,
            'fill' => $this->backgroundColor()->toString(),
        ];

        if ($this->drawable->hasBorder()) {
            $xmlAttributes['stroke'] = $this->borderColor()->toString();
            $xmlAttributes['stroke-width'] = $this->drawable->borderSize();
        }

        $ellipse = Driver::createShape('ellipse', $xmlAttributes, $image->width(), $image->height());

        $frames = [];
        foreach ($image as $frame) {
            $frames[] = $frame->setNative(
                $frame->native()->composite($ellipse, [BlendMode::OVER])
            );
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }
}
