<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Drivers\AbstractDriver;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\MissingDependencyException;
use Intervention\Image\FileExtension;
use Intervention\Image\Format;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ColorProcessorInterface;
use Intervention\Image\Interfaces\CoreInterface;
use Intervention\Image\Interfaces\FontProcessorInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Config as VipsConfig;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

class Driver extends AbstractDriver
{
    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::id()
     */
    public function id(): string
    {
        return 'vips';
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::createImage()
     *
     * @throws InvalidArgumentException
     * @throws DriverException
     */
    public function createImage(int $width, int $height): ImageInterface
    {
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Invalid image size. Only use int<1, max>');
        }

        try {
            $vipsImage = VipsImage::black(1, 1) // make a 1x1 pixel
                ->add(255) // add red channel
                ->cast(BandFormat::UCHAR) // cast to format
                ->embed(0, 0, $width, $height, ['extend' => Extend::COPY]) // extend to given width/height
                ->copy([
                    'interpretation' => Interpretation::SRGB,
                    'xres' => 96 / 25.4,
                    'yres' => 96 / 25.4,
                ]) // srgb
                ->bandjoin([
                    255, // green
                    255, // blue
                    0, // alpha
                ]);
        } catch (VipsException $e) {
            throw new DriverException('Failed to create new image', previous: $e);
        }

        return Image::usingDriver($this)->setCore(new Core($vipsImage));
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::createCore()
     *
     * @throws DriverException
     */
    public function createCore(array $frames): CoreInterface
    {
        return Core::createFromFrames($frames);
    }

    /**
     * @param array<string, string|int> $attributes
     * @throws DriverException
     */
    public static function createShape(string $shape, array $attributes, int $width, int $height): VipsImage
    {
        $xmlAttributes = implode(
            ' ',
            array_map(
                fn($key, $value) => sprintf('%s="%s"', $key, htmlspecialchars((string) $value)),
                array_keys($attributes),
                $attributes
            )
        );

        $svg = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg">' .
            '<' . $shape . ' ' . $xmlAttributes . ' />' .
            '</svg>';

        try {
            return VipsImage::svgload_buffer($svg);
        } catch (VipsException $e) {
            throw new DriverException('Failed to create geometric shape', previous: $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::colorProcessor()
     */
    public function colorProcessor(ImageInterface $image): ColorProcessorInterface
    {
        return new ColorProcessor($image);
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::fontProcessor()
     */
    public function fontProcessor(): FontProcessorInterface
    {
        return new FontProcessor();
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::supports()
     *
     * @throws DriverException
     */
    public function supports(string|Format|FileExtension|MediaType $identifier): bool
    {
        try {
            $format = Format::create($identifier);
        } catch (InvalidArgumentException) {
            return false;
        }

        return in_array($format, LoaderDetector::create()->formats());
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::checkHealth()
     */
    public function checkHealth(): void
    {
        try {
            // check health by calling Jcupitt\Vips\FFI::init()
            VipsConfig::version();
        } catch (VipsException $e) {
            throw new MissingDependencyException(
                'libvips does not seem to be installed correctly',
                previous: $e
            );
        }
    }

    /**
     * Return version of libvips library
     */
    public function version(): string
    {
        return VipsConfig::version();
    }
}
