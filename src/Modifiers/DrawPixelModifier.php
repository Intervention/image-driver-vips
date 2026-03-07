<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
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
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws StateException
     * @throws ModifierException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // decode pixel color
        $color = $this->driver()->colorProcessor($image)->export(
            $this->color()
        );

        try {
            $pixel = VipsImage::black(1, 1)
                ->add(array_slice($color, 0, 1)) // red
                ->cast($image->core()->native()->format)
                ->copy(['interpretation' => $image->core()->native()->interpretation])
                ->bandjoin(array_slice($color, 1));
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to draw pixel',
                previous: $e
            );
        }

        $frames = [];
        foreach ($image as $frame) {
            try {
                $native = $frame->native()->composite2(
                    $pixel,
                    BlendMode::OVER,
                    [
                        'x' => $this->position->x(),
                        'y' => $this->position->y(),
                    ],
                );
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to draw pixel',
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
