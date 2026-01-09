<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Frame;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\BackgroundModifier as GenericBackgroundModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;

class BackgroundModifier extends GenericBackgroundModifier implements SpecializedInterface
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
        // decode background color
        $bgColor = $this->driver()->colorProcessor($image)->colorToNative(
            $this->backgroundColor($this->driver())
        );

        // create new canvas with background color as background
        $canvas = $this->canvas($image, $bgColor);

        if ($image->isAnimated()) {
            $frames = [];
            foreach ($image as $frame) {
                try {
                    $frames[] = new Frame(
                        $canvas->core()->native()->composite2($frame->native(), BlendMode::OVER),
                        $frame->delay()
                    );
                } catch (VipsException $e) {
                    throw new ModifierException('Failed to blend background color', previous: $e);
                }
            }

            $image->core()->setNative(
                Core::replaceFrames($image->core()->native(), $frames)
            );

            return $image;
        }

        $image->core()->setNative(
            $canvas->core()->native()->composite2($image->core()->native(), BlendMode::OVER)
        );

        return $image;
    }

    /**
     * Create empty image with given background color in the size of the given image
     *
     * @param array<float> $color
     * @throws StateException
     * @throws ModifierException
     */
    private function canvas(ImageInterface $image, array $color): ImageInterface
    {
        try {
            $vipsImage = VipsImage::black(1, 1)
                ->add($color[0])
                ->cast($image->core()->native()->format)
                ->embed(0, 0, $image->width(), $image->height(), ['extend' => Extend::COPY])
                ->copy(['interpretation' => $image->core()->native()->interpretation])
                ->bandjoin(array_slice($color, 1));
        } catch (VipsException $e) {
            throw new ModifierException('Failed to blend background color', previous: $e);
        }

        $core = Core::ensureInMemory(new Core($vipsImage));

        return Image::usingDriver($this->driver())->setCore($core);
    }
}
