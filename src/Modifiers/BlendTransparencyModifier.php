<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Colors\Rgb\Color as RgbColor;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Frame;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\DriverInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\BlendTransparencyModifier as GenericBlendTransparencyModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;

class BlendTransparencyModifier extends GenericBlendTransparencyModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws VipsException
     * @throws RuntimeException
     * @throws ColorException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // decode blending color
        $color = $this->blendingColor($this->driver());

        // create new canvas with blending color as background
        $canvas = $this->canvas($image, $color);

        if ($image->isAnimated()) {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = new Frame(
                    $canvas->core()->native()->composite2($frame->native(), BlendMode::OVER),
                    $frame->delay()
                );
            }

            $image->core()->setNative(
                Core::replaceFrames($image->core()->native(), $frames)
            );

            return $image;
        }

        $canvas->core()->setNative(
            $canvas->core()->native()->composite2($image->core()->native(), BlendMode::OVER)
        );

        return $canvas;
    }

    /**
     * Create empty image with given background color in the size of the given image
     *
     * @param ImageInterface $image
     * @param ColorInterface $color
     * @throws ColorException
     * @throws VipsException
     * @throws RuntimeException
     * @return ImageInterface
     */
    private function canvas(ImageInterface $image, ColorInterface $color): ImageInterface
    {
        /** @var RgbColor $color */
        $vipsImage = VipsImage::black(1, 1)
            ->add($color->red()->value())
            ->cast($image->core()->native()->format)
            ->embed(0, 0, $image->width(), $image->height(), ['extend' => Extend::COPY])
            ->copy(['interpretation' => $image->core()->native()->interpretation])
            ->bandjoin([
                $color->green()->value(),
                $color->blue()->value(),
            ]);

        $core = Core::ensureInMemory(new Core($vipsImage));

        return new Image($this->driver(), $core);
    }

    /**
     * Decode current blending color of modifier
     *
     * TODO: Remove this method and use parent class implementation
     * (requires unreleased 'intervention/image' version)
     *
     * @param DriverInterface $driver
     * @throws RuntimeException
     * @throws DecoderException
     * @throws VipsException
     * @return ColorInterface
     */
    protected function blendingColor(DriverInterface $driver): ColorInterface
    {
        // decode blending color
        $color = $driver->handleInput(
            $this->color ?: $driver->config()->blendingColor
        );

        if (!($color instanceof ColorInterface)) {
            throw new DecoderException('Unable to decode blending color.');
        }

        return $color->convertTo(RgbColorspace::class);
    }
}
