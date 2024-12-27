<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Exceptions\AnimationException;
use Intervention\Image\Interfaces\CollectionInterface;
use Intervention\Image\Interfaces\CoreInterface;
use Intervention\Image\Interfaces\FrameInterface;
use IteratorAggregate;
use Jcupitt\Vips\Image as VipsImage;
use Traversable;

class Core implements CoreInterface, IteratorAggregate
{
    /**
     * Create new core instance
     *
     * @param VipsImage $vipsImage
     * @return void
     */
    public function __construct(protected VipsImage $vipsImage)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::native()
     */
    public function native(): mixed
    {
        return $this->vipsImage;
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::setNative()
     */
    public function setNative(mixed $native): CoreInterface
    {
        $this->vipsImage = $native;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::count()
     */
    public function count(): int
    {
        return $this->vipsImage->getType('n-pages') === 0 ? 1 : $this->vipsImage->get('n-pages');
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::frame()
     */
    public function frame(int $position): FrameInterface
    {
        if ($position > ($this->count() - 1)) {
            throw new AnimationException('Frame #' . $position . ' could not be found in the image.');
        }

        $height = $this->vipsImage->getType('page-height') === 0 ?
            $this->vipsImage->height : $this->vipsImage->get('page-height');

        try {
            return new Frame(
                $this->vipsImage->extract_area(
                    0,
                    $height * $position,
                    $this->vipsImage->width,
                    $height
                )
            );
        } catch (\Exception) {
            throw new AnimationException('Frame #' . $position . ' could not be found in the image.');
        }
    }

    public function add(FrameInterface $frame): CoreInterface
    {
        $frames = $this->toArray();
        $delay = $this->vipsImage->get('delay') ?? [];

        $frames[] = $frame->native();
        $delay[] = (int) $frame->delay();

        $this->vipsImage = VipsImage::arrayjoin($frames, ['across' => 1]);

        $this->vipsImage->set('delay', $delay);
        $this->vipsImage->set('loop', 0);
        $this->vipsImage->set('page-height', $frame->size()->height());
        $this->vipsImage->set('n-pages', count($frames));

        return $this;
    }

    public function loops(): int
    {
        return (int) $this->vipsImage->get('loop');
    }

    public function setLoops(int $loops): CoreInterface
    {
        $this->vipsImage->set('loop', $loops);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::first()
     */
    public function first(): FrameInterface
    {
        return $this->frame(0);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectableInterface::last()
     */
    public function last(): FrameInterface
    {
        return $this->frame($this->count() - 1);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::has()
     */
    public function has(int|string $key): bool
    {
        try {
            return !empty($this->frame($key));
        } catch (AnimationException) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::push()
     */
    public function push($item): CollectionInterface
    {
        return $this->add($item);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::get()
     */
    public function get(int|string $key, $default = null): mixed
    {
        try {
            return $this->frame($key);
        } catch (AnimationException) {
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::getAtPosition()
     */
    public function getAtPosition(int $key = 0, $default = null): mixed
    {
        return $this->get($key, $default);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::empty()
     */
    public function empty(): CollectionInterface
    {
        $this->vipsImage = VipsImage::black(1, 1)->cast($this->vipsImage->format);

        return $this;
    }

    public function toArray(): array
    {
        $frames = [];

        for ($i = 0; $i < $this->count(); $i++) {
            $f = $this->frame($i)->native()
                ->cast($this->vipsImage->format)
                ->copy(['interpretation' => $this->vipsImage->interpretation]);

            $frames[] = $f;
        }

        return $frames;
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::slice()
     */
    public function slice(int $offset, ?int $length = 0): CollectionInterface
    {
    }

    public function getIterator(): Traversable
    {
    }
}
