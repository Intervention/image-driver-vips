<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Geometry\Rectangle;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\DriverInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Jcupitt\Vips\Image as VipsImage;

class Frame implements FrameInterface
{
    public function __construct(protected VipsImage $vipsImage, protected float $delay = 0)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @see FrameInterface::native()
     */
    public function native(): mixed
    {
        return $this->vipsImage;
    }

    /**
     * {@inheritdoc}
     *
     * @see FrameInterface::setNative()
     */
    public function setNative($native): FrameInterface
    {
        $this->vipsImage = $native;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see FrameInterface::toImage()
     */
    public function toImage(DriverInterface $driver): ImageInterface
    {
        return new Image($driver, new Core($this->native()));
    }

    /**
     * {@inheritdoc}
     *
     * @see FrameInterface::size()
     */
    public function size(): SizeInterface
    {
        return new Rectangle(
            $this->vipsImage->width,
            $this->vipsImage->height,
        );
    }

    /**
     * {@inheritdoc}
     *
     * @see FrameInterface::delay()
     */
    public function delay(): float
    {
        return $this->delay;
    }

    /**
     * {@inheritdoc}
     *
     * @see FrameInterface::delay()
     */
    public function setDelay(float $delay): FrameInterface
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Currently not implemented by libvips
     *
     * @see FrameInterface::dispose()
     */
    public function dispose(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     *
     * Currently not implemented by libvips
     *
     * @see FrameInterface::dispose()
     */
    public function setDispose(int $dispose): FrameInterface
    {
        return $this;
    }

    public function setOffset(int $left, int $top): FrameInterface
    {
    }

    public function offsetLeft(): int
    {
        return $this->native()->get('xoffset');
    }

    public function setOffsetLeft(int $offset): FrameInterface
    {
        $this->native()->set('xoffset', $offset);

        return $this;
    }

    public function offsetTop(): int
    {
        return $this->native()->get('yoffset');
    }

    public function setOffsetTop(int $offset): FrameInterface
    {
        $this->native()->set('yoffset', $offset);

        return $this;
    }
}
