<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Colors\Cmyk\Color as CmykColor;
use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Colors\Rgb\Color as RgbColor;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ColorProcessorInterface;
use Intervention\Image\Interfaces\ColorspaceInterface;

class ColorProcessor implements ColorProcessorInterface
{
    /**
     * Create new ColorProcessor instance
     */
    public function __construct(protected ColorspaceInterface $colorspace)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @see ColorProcessorInterface::colorToNative()
     */
    public function colorToNative(ColorInterface $color)
    {
        return array_map(fn ($value) => $value * 255, $color->normalize());
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
}
