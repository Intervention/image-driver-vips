<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use ArrayIterator;
use Intervention\Image\Collection;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Interfaces\CollectionInterface;
use Intervention\Image\Interfaces\CoreInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Iterator;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Traversable;

/**
 * @implements Iterator<int, FrameInterface>
 */
class Core implements CoreInterface, Iterator
{
    protected int $iteratorIndex = 0;
    protected CollectionInterface $meta;

    /**
     * Create new core instance
     *
     * @return void
     */
    public function __construct(protected VipsImage $vipsImage)
    {
        $this->meta = new Collection();
    }

    /**
     * @param list<FrameInterface> $frames
     * @throws DriverException
     */
    public static function createFromFrames(array $frames, int $loops = 0): self
    {
        $natives = [];
        $delay = [];

        foreach ($frames as $frame) {
            $delay[] = intval($frame->delay() * 1000);
            $natives[] = $frame->native();
        }

        try {
            $image = VipsImage::arrayjoin($natives, ['across' => 1]);
            $image->set('delay', $delay);
            $image->set('loop', $loops);
            $image->set('page-height', $natives[0]->height);
            $image->set('n-pages', count($frames));
        } catch (VipsException $e) {
            throw new DriverException('Failed to create image core from frames', previous: $e);
        }

        return new self($image);
    }

    public function meta(): CollectionInterface
    {
        return $this->meta;
    }

    /**
     * @throws NotSupportedException
     */
    public function set(int|string $key, mixed $item): CollectionInterface
    {
        throw new NotSupportedException('Not implemented');
    }

    /**
     * @throws InvalidArgumentException
     * @throws DriverException
     */
    public function at(int $key = 0, mixed $default = null): mixed
    {
        return $this->frame($key);
    }

    /**
     * @throws NotSupportedException
     */
    public function clear(): CollectionInterface
    {
        throw new NotSupportedException('Not implemented');
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
     * Renders vips image of given core into memory and serves any downstream
     * requests from the memory area
     */
    public static function ensureInMemory(CoreInterface $core): CoreInterface
    {
        if (!in_array('vips-sequential', $core->native()->getFields())) {
            return $core;
        }

        if (false === (bool) $core->native()->get('vips-sequential')) {
            return $core;
        }

        $core->setNative($core->native()->copyMemory());

        return $core;
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::count()
     *
     * @throws DriverException
     */
    public function count(): int
    {
        try {
            return $this->vipsImage->getType('n-pages') === 0 ? 1 : $this->vipsImage->get('n-pages');
        } catch (VipsException $e) {
            throw new DriverException('Failed to count image frames', previous: $e);
        }
    }

    /**
     * @param list<FrameInterface> $frames
     * @throws DriverException
     */
    public static function replaceFrames(VipsImage $vipsImage, array $frames): VipsImage
    {
        try {
            $loops = in_array('loop', $vipsImage->getFields()) ? $vipsImage->get('loop') : 0;
        } catch (VipsException $e) {
            throw new DriverException('Failed to replace frames', previous: $e);
        }

        return self::createFromFrames($frames, $loops)->native();
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::frame()
     *
     * @throws InvalidArgumentException
     * @throws DriverException
     */
    public function frame(int $position): FrameInterface
    {
        $count = $this->count();

        if ($position > ($count - 1)) {
            throw new InvalidArgumentException('Frame #' . $position . ' could not be found in the image.');
        }

        if ($count === 1) {
            return new Frame($this->vipsImage);
        }

        try {
            $sequential = in_array('vips-sequential', $this->vipsImage->getFields()) ?
                $this->vipsImage->get('vips-sequential') : null;

            if ($sequential) {
                $this->vipsImage = $this->vipsImage->copyMemory();
            }

            $delay = in_array('delay', $this->vipsImage->getFields()) ?
                ($this->vipsImage->get('delay')[$position] ?? 0) : null;

            $height = $this->vipsImage->getType('page-height') === 0 ?
                $this->vipsImage->height : $this->vipsImage->get('page-height');

            // extract only certain frame
            $vipsImage = $this->vipsImage->extract_area(
                0,
                $height * $position,
                $this->vipsImage->width,
                $height
            );

            $vipsImage->set('n-pages', 1);
            if (!is_null($delay)) {
                $vipsImage->set('delay', $delay);

                return new Frame($vipsImage, $delay / 1000);
            }
        } catch (VipsException $e) {
            throw new DriverException('Failed to extract frame from image core', previous: $e);
        }

        return new Frame($vipsImage);
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::add()
     *
     * @throws DriverException
     */
    public function add(FrameInterface $frame): self
    {
        $frames = $this->toArray();
        $frames[] = $frame;

        $this->setNative(self::replaceFrames($this->vipsImage, $frames));

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::loops()
     *
     * @throws DriverException
     */
    public function loops(): int
    {
        try {
            return (int) $this->vipsImage->get('loop');
        } catch (VipsException $e) {
            throw new DriverException('Failed to load loop count', previous: $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::setLoops()
     *
     * @throws DriverException
     */
    public function setLoops(int $loops): CoreInterface
    {
        try {
            $this->vipsImage->set('loop', $loops);
        } catch (VipsException $e) {
            throw new DriverException('Failed to set loop count', previous: $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::first()
     *
     * @throws InvalidArgumentException
     * @throws DriverException
     */
    public function first(): FrameInterface
    {
        return $this->frame(0);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectableInterface::last()
     *
     * @throws InvalidArgumentException
     * @throws DriverException
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
        return $this->get($key) instanceof FrameInterface;
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::push()
     *
     * @throws DriverException
     */
    public function push(mixed $item): CollectionInterface
    {
        return $this->add($item);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::get()
     */
    public function get(int|string $key, mixed $default = null): mixed
    {
        try {
            return $this->frame(intval($key));
        } catch (ImageException) {
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::getAtPosition()
     */
    public function getAtPosition(int $key = 0, mixed $default = null): mixed
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
     * @throws DriverException
     * @return list<FrameInterface>
     */
    public function toArray(): array
    {
        $frames = [];

        try {
            for ($i = 0; $i < $this->count(); $i++) {
                $frames[] = $this->frame($i);
            }
        } catch (InvalidArgumentException $e) {
            throw new DriverException('Failed to cast ' . $this::class . ' to array', previous: $e);
        }

        return $frames;
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::slice()
     *
     * @throws DriverException
     */
    public function slice(int $offset, ?int $length = 0): CollectionInterface
    {
        $frames = $this->toArray();

        $frames = array_slice($frames, $offset, $length);
        $this->setNative(self::replaceFrames($this->vipsImage, $frames));

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
     * {@inheritdoc}
     *
     * @see Iterator::valid()
     */
    public function valid(): bool
    {
        return $this->has($this->iteratorIndex);
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::current()
     */
    public function current(): mixed
    {
        return $this->get($this->iteratorIndex);
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::next()
     */
    public function next(): void
    {
        $this->iteratorIndex += 1;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::key()
     */
    public function key(): mixed
    {
        return $this->iteratorIndex;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::rewind()
     */
    public function rewind(): void
    {
        $this->iteratorIndex = 0;
    }

    /**
     * Show debug info for the current image
     *
     * @throws DriverException
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $debug = [];

        try {
            foreach ($this->vipsImage->getFields() as $name) {
                $value = $this->vipsImage->get($name);

                if (str_ends_with($name, "-data")) {
                    $len = strlen($value);
                    $value = "<$len bytes of binary data>";
                }

                $debug[$name] = is_array($value) ? implode(", ", $value) : (string) $value;
            }
        } catch (VipsException $e) {
            throw new DriverException('Failed to read image field names', previous: $e);
        }

        return $debug;
    }
}
