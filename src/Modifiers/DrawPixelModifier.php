<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\AnimationException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\DrawPixelModifier as GenericDrawPixelModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Exception as VipsException;

class DrawPixelModifier extends GenericDrawPixelModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws VipsException|AnimationException
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $pixel = VipsImage::black(1, 1)
            ->add(255) // red
            ->cast($image->core()->native()->format)
            ->copy(['interpretation' => $image->core()->native()->interpretation])
            ->bandjoin([
                255, // green
                255, // blue
                255, // alpha
            ]);

        $frames = [];
        foreach ($image as $frame) {
            $frames[] = $frame->setNative(
                $frame->native()->composite2(
                    $pixel,
                    BlendMode::OVER,
                    [
                        'x' => $this->position->x(),
                        'y' => $this->position->y(),
                    ],
                )
            );
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }
}
