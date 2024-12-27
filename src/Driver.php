<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Drivers\AbstractDriver;
use Intervention\Image\Exceptions\DriverException;
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
use Jcupitt\Vips\Exception;
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
     * @throws Exception|RuntimeException
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
     * @throws RuntimeException|Exception
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
             * @throws RuntimeException|Exception
             */
            public function __invoke(): ImageInterface
            {
                $frames = [];
                $delay = [];

                foreach ($this->frames as $frame) {
                    $delay = array_merge($delay, $frame->native()->get('delay'));
                    $frames[] = $frame->native();
                }

                $image = VipsImage::arrayjoin($frames, ['across' => 1]);
                $image->set('delay', $delay);
                $image->set('loop', 0);
                $image->set('page-height', $frames[0]->height);
                $image->set('n-pages', count($this->frames));

                return new Image(
                    $this->driver,
                    new Core($image)
                );
            }
        };

        $init($animation);

        return call_user_func($animation);
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
     * @see DriverInterface::supports()
     */
    public function supports(string|Format|FileExtension|MediaType $identifier): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::checkHealth()
     */
    public function checkHealth(): void
    {
        if (!extension_loaded('ffi') && !extension_loaded('vips')) {
            throw new DriverException(
                'PHP extension FFI or VIPS must be enabled to use this driver.'
            );
        }

        if (version_compare(PHP_VERSION, '8.3', '>=') && ini_get('zend.max_allowed_stack_size') != '-1') {
            throw new DriverException("zend.max_allowed_stack_size not set to '-1'");
        }
    }
}
