<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Colors\Rgb\Color as RgbColor;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\BlendTransparencyModifier as GenericBlendTransparencyModifier;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;

class BlendTransparencyModifier extends GenericBlendTransparencyModifier implements SpecializedInterface
{
    public function apply(ImageInterface $image): ImageInterface
    {
        // decode blending color
        $color = $this->blendingColor();

        // create new canvas with blending color as background
        $canvas = $this->canvas($image, $color);

        // place original image
        $canvas->modify(new PlaceModifier($image));

        return $canvas;
    }

    private function canvas(ImageInterface $image, RgbColor $color): ImageInterface
    {
        $vipsImage = VipsImage::black(1, 1)
            ->add($color->red()->value())
            ->cast(BandFormat::UCHAR)
            ->embed(0, 0, $image->width(), $image->height(), ['extend' => Extend::COPY])
            ->copy(['interpretation' => ColorProcessor::colorspaceToInterpretation($image->colorspace())])
            ->bandjoin([
                $color->green()->value(),
                $color->blue()->value(),
                $color->alpha()->value(),
            ]);

        return new Image($this->driver(), new Core($vipsImage));
    }

    private function blendingColor(): RgbColor
    {
        // decode blending color
        $color = $this->driver()->handleInput(
            $this->color ? $this->color : $this->driver()->config()->blendingColor
        );

        if (!($color instanceof ColorInterface)) {
            throw new DecoderException('Unable to decode blending color.');
        }

        return $color->convertTo(RgbColorspace::class);
    }
}
