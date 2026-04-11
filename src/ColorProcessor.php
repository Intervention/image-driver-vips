<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use DivisionByZeroError;
use Intervention\Image\Colors\Cmyk\Colorspace as Cmyk;
use Intervention\Image\Colors\Hsl\Colorspace as Hsl;
use Intervention\Image\Colors\Hsv\Colorspace as Hsv;
use Intervention\Image\Colors\Oklab\Colorspace as Oklab;
use Intervention\Image\Colors\Oklch\Colorspace as Oklch;
use Intervention\Image\Colors\Rgb\Colorspace as Rgb;
use Intervention\Image\Exceptions\ColorDecoderException;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Interfaces\ColorChannelInterface;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ColorProcessorInterface;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class ColorProcessor implements ColorProcessorInterface
{
    protected ColorspaceInterface $colorspace;
    protected VipsImage $baseImage;

    /**
     * Create new ColorProcessor instance
     *
     * @throws DriverException
     */
    public function __construct(ImageInterface $image)
    {
        $this->colorspace = $image->colorspace();

        $baseImage = $image->core()->native();
        if (!$baseImage instanceof VipsImage) {
            throw new DriverException(
                'Unable to extract color processor base image of type ' . VipsImage::class,
            );
        }

        $this->baseImage = $baseImage;
    }

    /**
     * {@inheritdoc}
     *
     * @see ColorProcessorInterface::colorspace()
     */
    public function colorspace(): ColorspaceInterface
    {
        return $this->colorspace;
    }

    /**
     * {@inheritdoc}
     *
     * @see ColorProcessorInterface::export()
     *
     * @throws ColorException
     */
    public function export(ColorInterface $color): mixed
    {
        // transform color to current colorspace and extract bands
        $bands = array_map(
            fn(ColorChannelInterface $channel): float => $channel->normalized() * 255,
            $color->toColorspace($this->colorspace)->channels(),
        );

        try {
            // handle grayscale colors
            if ($this->baseImage->bands === 1 && count($bands) > 1) {
                $bands = array_slice($bands, 0, 3);
                return [round(array_sum($bands) / count($bands))];
            }

            // handle grayscale colors with alpha
            if ($this->baseImage->bands === 2 && count($bands) > 2) {
                $alpha = array_slice($bands, -1)[0];
                $bands = array_slice($bands, 0, 3);
                return [round(array_sum($bands) / count($bands)), $alpha];
            }

            if (count($bands) > $this->baseImage->bands) {
                return array_slice($bands, 0, $this->baseImage->bands);
            }
        } catch (DivisionByZeroError $e) {
            throw new ColorException(
                'Failed to import color ' . $color::class . ' to ' . $this::class,
                previous: $e,
            );
        }

        return $bands;
    }

    /**
     * {@inheritdoc}
     *
     * @see ColorProcessorInterface::import()
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws ColorDecoderException
     */
    public function import(mixed $color): ColorInterface
    {
        if (!is_array($color)) {
            throw new InvalidArgumentException($this::class . ' can only decode colors in array format');
        }

        if (count($color) === 1) {
            // normalize single band color
            $normalized = array_pad($color, count($this->requiredChannels()), $color[0]);
        }

        if (count($color) === 2) {
            // normalize single band + alpha
            $normalized = array_fill(0, count($this->requiredChannels()), $color[0]);
            $normalized[] = $color[1]; // apend alpha value
        }

        // "native color" means array of normalized to 0-255 color channel values
        $normalized = array_map(fn(int $value): float => $value / 255, $normalized ?? $color);

        return match ($this->colorspace::class) {
            Cmyk::class => $this->colorspace->colorFromNormalized($normalized),
            Rgb::class => $this->colorspace->colorFromNormalized($normalized),
            Hsl::class => Rgb::class::colorFromNormalized($normalized)->toColorspace(Hsl::class),
            Hsv::class => Rgb::colorFromNormalized($normalized)->toColorspace(Hsv::class),
            Oklab::class => Rgb::colorFromNormalized($normalized)->toColorspace(Oklab::class),
            Oklch::class => Rgb::colorFromNormalized($normalized)->toColorspace(Oklch::class),
            default => throw new NotSupportedException(
                'Colorspace ' . $this->colorspace::class . ' is not supported by driver'
            )
        };
    }

    /**
     * Transform vips interpretation into colorspace object
     *
     * @throws ColorDecoderException
     */
    public static function interpretationToColorspace(string $interpretation): ColorspaceInterface
    {
        return match ($interpretation) {
            Interpretation::MULTIBAND => new Rgb(),
            Interpretation::B_W => new Rgb(),
            Interpretation::HISTOGRAM => new Rgb(),
            Interpretation::FOURIER => new Rgb(),
            Interpretation::XYZ => new Rgb(),
            Interpretation::LAB => new Rgb(),
            Interpretation::CMYK => new Cmyk(),
            Interpretation::LABQ => new Rgb(),
            Interpretation::RGB => new Rgb(),
            Interpretation::CMC => new Rgb(),
            Interpretation::LCH => new Rgb(),
            Interpretation::LABS => new Rgb(),
            Interpretation::SRGB => new Rgb(),
            Interpretation::HSV => new Hsv(),
            Interpretation::SCRGB => new Rgb(),
            Interpretation::XYZ => new Rgb(),
            Interpretation::RGB16 => new Rgb(),
            Interpretation::GREY16 => new Rgb(),
            Interpretation::MATRIX => new Rgb(),
            default => throw new ColorDecoderException(
                'Unable to transform interpretation "' . $interpretation . '" to colorspace.',
            ),
        };
    }

    /**
     * Transform colorspace into vips interpretation
     */
    public static function colorspaceToInterpretation(string|ColorspaceInterface $colorspace): string
    {
        return match (is_string($colorspace) ? $colorspace : $colorspace::class) {
            Cmyk::class => Interpretation::CMYK,
            Hsv::class => Interpretation::HSV,
            default => Interpretation::SRGB,
        };
    }

    /**
     * Return classnames of the required color channels of the current colorspace.
     *
     * @throws ColorDecoderException
     * @return array<string>
     */
    private function requiredChannels(): array
    {
        try {
            return array_filter($this->colorspace::channels(), function (string $classname): bool {
                $requredParams = array_filter(
                    (new ReflectionClass($classname))->getConstructor()->getParameters(),
                    function (ReflectionParameter $parameter): bool {
                        try {
                            $parameter->getDefaultValue();
                        } catch (ReflectionException) {
                            return true;
                        }

                        return false;
                    }
                );

                return count($requredParams) > 0;
            });
        } catch (ReflectionException $e) {
            throw new ColorDecoderException(
                'Failed to load classnames of required color channels',
                previous: $e,
            );
        }
    }
}
