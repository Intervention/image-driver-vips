<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Drivers\AbstractDriver;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\FileExtension;
use Intervention\Image\Format;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\DriverInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\ColorProcessorInterface;
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
     * @throws VipsException|RuntimeException
     */
    public function createImage(int $width, int $height): ImageInterface
    {
        $vipsImage = VipsImage::black(1, 1) // make a 1x1 pixel
            ->add(255) // add red channel
            ->cast(BandFormat::UCHAR) // cast to format
            ->embed(0, 0, $width, $height, ['extend' => Extend::COPY]) // extend to given width/height
            ->copy(['interpretation' => Interpretation::SRGB]) // srgb
            ->bandjoin([
                255, // green
                255, // blue
                0, // alpha
            ]);

        return new Image($this, new Core($vipsImage));
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::createAnimation()
     * @throws RuntimeException|VipsException
     */
    public function createAnimation(callable $init): ImageInterface
    {
        $animation = new class ($this)
        {
            /**
             * @var list<FrameInterface>
             */
            protected array $frames = [];

            public function __construct(
                protected DriverInterface $driver,
            ) {
                //
            }

            /**
             * @throws RuntimeException
             */
            public function add(mixed $source, float $delay = 1): self
            {
                $this->frames[] = $this->driver->handleInput($source)->core()->first()->setDelay($delay);

                return $this;
            }

            /**
             * @throws RuntimeException|VipsException
             */
            public function __invoke(): ImageInterface
            {
                return new Image(
                    $this->driver,
                    Core::createFromFrames($this->frames)
                );
            }
        };

        $init($animation);

        return call_user_func($animation);
    }

    /**
     * @param string $shape
     * @param array<string, string|int> $attributes
     * @param int $width
     * @param int $height
     * @throws RuntimeException
     * @return VipsImage
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
            throw new RuntimeException('Could not create shape: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::colorProcessor()
     */
    public function colorProcessor(ColorspaceInterface $colorspace): ColorProcessorInterface
    {
        return new ColorProcessor($colorspace);
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
     * @throws RuntimeException
     * @see DriverInterface::supports()
     */
    public function supports(string|Format|FileExtension|MediaType $identifier): bool
    {
        try {
            $format = Format::create($identifier);
        } catch (NotSupportedException) {
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
            throw new DriverException(
                'libvips does not seem to be installed or not correctly configured.',
                previous: $e
            );
        }
    }

    /**
     * Return version of libvips library
     *
     * @return string
     */
    public static function version(): string
    {
        return VipsConfig::version();
    }
}
