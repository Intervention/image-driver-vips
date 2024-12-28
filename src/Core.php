<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use ArrayIterator;
use Intervention\Image\Exceptions\AnimationException;
use Intervention\Image\Interfaces\CollectionInterface;
use Intervention\Image\Interfaces\CoreInterface;
use Intervention\Image\Interfaces\FrameInterface;
use IteratorAggregate;
use Jcupitt\Vips\Exception;
use Jcupitt\Vips\Image as VipsImage;
use Traversable;

/**
 * @implements IteratorAggregate<int, FrameInterface>
 */
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
     * @throws Exception
     */
    public function count(): int
    {
        return $this->vipsImage->getType('n-pages') === 0 ? 1 : $this->vipsImage->get('n-pages');
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::frame()
     * @throws AnimationException|Exception
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

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::add()
     * @throws AnimationException|Exception
     */
    public function add(FrameInterface $frame): CoreInterface
    {
        $frames = $this->toArray();
        $delay = $this->vipsImage->get('delay') ?? [];

        $frames[] = $frame->native();
        $delay[] = (int) $frame->delay();

        $this->vipsImage = VipsImage::arrayjoin($frames, ['across' => 1]);

        $this->vipsImage->set('delay', $delay);
        $this->vipsImage->set('loop', $this->loops());
        $this->vipsImage->set('page-height', $frame->size()->height());
        $this->vipsImage->set('n-pages', count($frames));

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::loops()
     * @throws Exception
     */
    public function loops(): int
    {
        return (int) $this->vipsImage->get('loop');
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::setLoops()
     * @throws Exception
     */
    public function setLoops(int $loops): CoreInterface
    {
        $this->vipsImage->set('loop', $loops);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::first()
     * @throws AnimationException|Exception
     */
    public function first(): FrameInterface
    {
        return $this->frame(0);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectableInterface::last()
     * @throws AnimationException|Exception
     */
    public function last(): FrameInterface
    {
        return $this->frame($this->count() - 1);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::has()
     * @throws Exception
     */
    public function has(int|string $key): bool
    {
        try {
            return (bool) $this->frame($key);
        } catch (AnimationException) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::push()
     * @throws AnimationException|Exception
     */
    public function push($item): CollectionInterface
    {
        return $this->add($item);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::get()
     * @throws Exception
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
     * @throws Exception
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

    /**
     * @return list<VipsImage>
     *
     * @throws AnimationException|Exception
     */
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
     * @throws AnimationException|Exception
     */
    public function slice(int $offset, ?int $length = 0): CollectionInterface
    {
        $frames = $this->toArray();
        $delay = $this->vipsImage->get('delay') ?? [];

        $frames = array_slice($frames, $offset, $length);
        $delay = array_slice($delay, $offset, $length);

        $this->vipsImage = VipsImage::arrayjoin($frames, ['across' => 1]);

        $this->vipsImage->set('delay', $delay);
        $this->vipsImage->set('loop', $this->loops());
        $this->vipsImage->set('page-height', $frames[0]->height);
        $this->vipsImage->set('n-pages', count($frames));

        return $this;
    }

    /**
     * Implementation of IteratorAggregate
     *
     * @return Traversable<FrameInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this); // @phpstan-ignore-line
    }

    /**
     * Show debug info for the current image
     *
     * @throws Exception
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $debug = [];

        foreach ($this->vipsImage->getFields() as $name) {
            $value = $this->vipsImage->get($name);

            if (str_ends_with($name, "-data")) {
                $len = strlen($value);
                $value = "<$len bytes of binary data>";
            }

            if (is_array($value)) {
                $value = implode(", ", $value);
            } else {
                $value = (string) $value;
            }

            $debug[$name] = $value;
        }

        return $debug;
    }
}
