<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Colors\Cmyk\Color as CmykColor;
use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Colors\Hsv\Colorspace as HsvColorspace;
use Intervention\Image\Colors\Rgb\Color as RgbColor;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ColorProcessorInterface;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Jcupitt\Vips\Interpretation;

class ColorProcessor implements ColorProcessorInterface
{
    /**
     * Create new ColorProcessor instance
     */
    public function __construct(protected ColorspaceInterface $colorspace)
    {
        //
    }

    /**
     * {@inheritdoc}
     *
     * @see ColorProcessorInterface::colorToNative()
     */
    public function colorToNative(ColorInterface $color)
    {
        return array_map(fn(float $value) => $value * 255, $color->normalize());
    }

    /**
     * {@inheritdoc}
     *
     * @see ColorProcessorInterface::nativeToColor()
     */
    public function nativeToColor(mixed $native): ColorInterface
    {
        if (!is_array($native)) {
            throw new ColorException('Vips driver can only decode colors in array format.');
        }

        return match ($this->colorspace::class) {
            CmykColorspace::class => new CmykColor(...$native),
            default => new RgbColor(...$native),
        };
    }

    /**
     * Transform vips interpretation into colorspace object
     *
     * @throws ColorException
     */
    public static function interpretationToColorspace(string $interpretation): ColorspaceInterface
    {
        return match ($interpretation) {
            Interpretation::MULTIBAND => new RgbColorspace(),
            Interpretation::B_W => new RgbColorspace(),
            Interpretation::HISTOGRAM => new RgbColorspace(),
            Interpretation::FOURIER => new RgbColorspace(),
            Interpretation::XYZ => new RgbColorspace(),
            Interpretation::LAB => new RgbColorspace(),
            Interpretation::CMYK => new CmykColorspace(),
            Interpretation::LABQ => new RgbColorspace(),
            Interpretation::RGB => new RgbColorspace(),
            Interpretation::CMC => new RgbColorspace(),
            Interpretation::LCH => new RgbColorspace(),
            Interpretation::LABS => new RgbColorspace(),
            Interpretation::SRGB => new RgbColorspace(),
            Interpretation::HSV => new HsvColorspace(),
            Interpretation::SCRGB => new RgbColorspace(),
            Interpretation::XYZ => new RgbColorspace(),
            Interpretation::RGB16 => new RgbColorspace(),
            Interpretation::GREY16 => new RgbColorspace(),
            Interpretation::MATRIX => new RgbColorspace(),
            default => throw new ColorException(
                'Unable to transform interpretation "' . $interpretation . '" to colorspace.',
            ),
        };
    }

    /**
     * Transform colorspace into vips interpretation
     */
    public static function colorspaceToInterpretation(string|ColorspaceInterface $colorspace): string
    {
        $classname = is_string($colorspace) ? $colorspace : $colorspace::class;

        return match ($classname) {
            RgbColorspace::class => Interpretation::SRGB,
            CmykColorspace::class => Interpretation::CMYK,
            HsvColorspace::class => Interpretation::HSV,
            default => Interpretation::SRGB,
        };
    }
}
