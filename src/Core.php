<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use ArrayIterator;
use Intervention\Image\Collection;
use Intervention\Image\Drivers\Vips\Source\BufferSource;
use Intervention\Image\Drivers\Vips\Source\PathSource;
use Intervention\Image\Drivers\Vips\Traits\CanNormalizeSource;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Interfaces\CollectionInterface;
use Intervention\Image\Interfaces\CoreInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Iterator;
use Jcupitt\Vips\Access;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Traversable;

/**
 * @implements Iterator<int, FrameInterface>
 */
class Core implements CoreInterface, Iterator
{
    use CanNormalizeSource;

    /**
     * Number of operations that may be chained onto the image before its
     * pipeline is rendered into memory.
     *
     * libvips evaluates a pipeline by recursing once per node, on a worker
     * thread whose stack is fixed when the thread is created. A long enough
     * chain overruns that stack and takes the process down with SIGBUS or
     * SIGSEGV, with no catchable error and nothing on stderr. The ceiling is
     * the platform's default thread stack size, measured on a chain of
     * composites at 198 nodes on macOS arm64, 1843 on musl and 2009 on glibc.
     * Callers that build an image in a loop, tiling a watermark or drawing
     * many shapes, reach it with ordinary inputs.
     *
     * Rendering to memory every N operations keeps the depth well under the
     * lowest of those ceilings. Chains shorter than N, which is every normal
     * use of the driver, never pay for it. Past N there is a cost: a rendered
     * image is a whole image, so an operation that later narrows the region of
     * interest, a crop after the chain say, no longer gets to narrow what the
     * chain computes.
     *
     * This counts calls, not libvips nodes, so it is a bound rather than a
     * measurement. A modifier that chains several nodes per call spends the
     * budget faster than one node per call. The frame round trip of add()
     * and slice() is the deepest, four nodes per call: extract_area() and
     * arrayjoin(), each followed by the header copy their fields go on.
     */
    public const MAX_CHAINED_OPERATIONS = 32;

    protected int $iteratorIndex = 0;
    protected int $chainedOperations = 0;
    protected bool $metaStripped = false;
    protected CollectionInterface $meta;
    protected null|PathSource|BufferSource $stashedSource = null;

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
            // the joined image comes out of the libvips operation cache, the
            // same frames give the same image again. Set the fields on a
            // private copy of the header, or they reach every core built from
            // these frames
            $vipsImage = VipsImage::arrayjoin($natives, ['across' => 1])->copy();
            $vipsImage->set('delay', $delay);
            $vipsImage->set('loop', $loops);
            $vipsImage->set('n-pages', count($frames));

            if (count($frames) > 1) {
                $vipsImage->set('page-height', $natives[0]->height);
            } elseif (in_array('page-height', $vipsImage->getFields())) {
                $vipsImage->remove('page-height');
            }
        } catch (VipsException $e) {
            throw new DriverException('Failed to create image core from frames', previous: $e);
        }

        return new self($vipsImage);
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::map()
     *
     * @throws NotSupportedException
     */
    public function map(callable $callback): CollectionInterface
    {
        throw new NotSupportedException('Not implemented');
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::filter()
     *
     * @throws NotSupportedException
     */
    public function filter(callable $callback): CollectionInterface
    {
        throw new NotSupportedException('Not implemented');
    }

    /**
     * {@inheritdoc}
     *
     * @see CollectionInterface::meta()
     */
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
     *
     * @throws InvalidArgumentException
     * @throws DriverException
     */
    public function setNative(mixed $native): CoreInterface
    {
        if (!$native instanceof VipsImage) {
            throw new InvalidArgumentException(
                'Value for argument setNative() "$native" must be instanceof of ' . VipsImage::class,
            );
        }

        $this->vipsImage = $native;
        $this->stashedSource = null;

        if (++$this->chainedOperations >= static::MAX_CHAINED_OPERATIONS) {
            $this->vipsImage = self::renderToMemory($this->vipsImage);
            $this->chainedOperations = 0;
        }

        return $this;
    }

    /**
     * Return the stashed source ref set by the decoder, or null if none is
     * stashed (either never set, or cleared by a setNative() call).
     */
    public function stashedSource(): null|PathSource|BufferSource
    {
        return $this->stashedSource;
    }

    /**
     * Stash a source ref on this core. Set by decoders right after a clean
     * decode so resize-family modifiers can use libvips' combined
     * load+resize ops. Cleared automatically the next time setNative() runs.
     */
    public function setStashedSource(PathSource|BufferSource $source): self
    {
        $this->stashedSource = $source;

        return $this;
    }

    /**
     * Whether the meta data of this core has been stripped.
     *
     * libvips builds an EXIF block from the image's core fields at save time,
     * whether or not the image carries one, so removing the fields from the
     * image cannot keep meta data out of the encoded result on its own. The
     * encoders read this flag to pass the matching keep option to the save.
     */
    public function metaStripped(): bool
    {
        return $this->metaStripped;
    }

    /**
     * Mark the meta data of this core as stripped. Set by StripMetaModifier
     * and, unlike the stashed source, kept across setNative() so the flag
     * survives any modifier that runs between the strip and the encoding.
     *
     * It does not survive a new core built around the same vips image, as
     * Frame::toImage() and createFromFrames() do. Such an image carries no
     * meta data, its fields were removed, but libvips synthesises an EXIF
     * block for it again at save time.
     */
    public function setMetaStripped(bool $stripped = true): self
    {
        $this->metaStripped = $stripped;

        return $this;
    }

    /**
     * Renders vips image of given core into memory and serves any downstream
     * requests from the memory area
     *
     * @throws DriverException
     */
    public static function ensureInMemory(CoreInterface $core): CoreInterface
    {
        $native = $core->native();

        if (!$native instanceof VipsImage) {
            throw new DriverException(
                'Failed to render image pipeline into memory, core is not backed by ' . VipsImage::class,
            );
        }

        if (self::isSequential($native)) {
            $core->setNative(self::renderToMemory($native));
        }

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
            throw new InvalidArgumentException('Frame #' . $position . ' could not be found in the image');
        }

        if ($count === 1) {
            return new Frame($this->vipsImage);
        }

        try {
            if (self::isSequential($this->vipsImage)) {
                $this->vipsImage = self::renderToMemory($this->vipsImage);
            }

            $delay = in_array('delay', $this->vipsImage->getFields()) ?
                ($this->vipsImage->get('delay')[$position] ?? 0) : null;

            $height = $this->vipsImage->getType('page-height') === 0 ?
                $this->vipsImage->height : $this->vipsImage->get('page-height');

            // extract only certain frame, on a private copy of the header: the
            // extracted area comes out of the libvips operation cache and a
            // later extraction of the same area would get the fields set below
            $vipsImage = $this->vipsImage->extract_area(
                0,
                $height * $position,
                $this->vipsImage->width,
                $height,
            )->copy();

            $vipsImage->set('n-pages', 1);
            if (!is_null($delay)) {
                $vipsImage->set('delay', [$delay]);

                return new Frame($vipsImage, $delay / 1000);
            }

            return new Frame($vipsImage);
        } catch (VipsException $e) {
            throw new DriverException('Failed to extract frame from image core', previous: $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see CoreInterface::add()
     *
     * @throws DriverException
     */
    public function add(FrameInterface $frame): CoreInterface
    {
        $frames = $this->toArray();
        $frames[] = $frame;

        // @phpstan-ignore missingType.checkedException
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
            // an image decoded from a still source carries no loop field, GD
            // and Imagick report 0 there
            if ($this->vipsImage->getType('loop') === 0) {
                return 0;
            }

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
            // work on a copy, the vips image is shared with any clone of the
            // image and setting the field in place would change both
            $native = $this->vipsImage->copy();
            $native->set('loop', $loops);
        } catch (VipsException $e) {
            throw new DriverException('Failed to set loop count', previous: $e);
        }

        // this also clears the stashed source, a clone reopens it and would
        // otherwise come back with the loop count of the file
        // @phpstan-ignore missingType.checkedException
        $this->setNative($native);

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
     *
     * @throws DriverException
     */
    public function empty(): CollectionInterface
    {
        try {
            $empty = VipsImage::black(1, 1)->cast($this->vipsImage->format);
        } catch (VipsException $e) {
            throw new DriverException('Failed to empty image core', previous: $e);
        }

        // this also clears the stashed source, a clone reopens it and would
        // otherwise bring the emptied source back
        // @phpstan-ignore missingType.checkedException
        $this->setNative($empty);

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
    public function slice(int $offset, ?int $length = null): CollectionInterface
    {
        $frames = $this->toArray();
        $frames = array_slice($frames, $offset, $length);

        // @phpstan-ignore missingType.checkedException
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
     * Reopen the stashed source the way the decoder loaded it: the same
     * option string, sequential access, and the decoder's normalisation on
     * top.
     *
     * @throws DriverException
     */
    private function reopenStashedSource(PathSource|BufferSource $source): VipsImage
    {
        try {
            $vipsImage = $source instanceof PathSource
                ? VipsImage::newFromFile($source->pathWithOptions(), ['access' => Access::SEQUENTIAL])
                : VipsImage::newFromBuffer($source->buffer, $source->optionString, ['access' => Access::SEQUENTIAL]);

            return $this->normalizeSource($vipsImage);
        } catch (VipsException $e) {
            throw new DriverException('Failed to reopen the image source for the clone', previous: $e);
        }
    }

    /**
     * Whether the given vips image still has to be read in a single
     * sequential pass, the way the decoders load it.
     *
     * @throws DriverException
     */
    private static function isSequential(VipsImage $vipsImage): bool
    {
        if ($vipsImage->getType('vips-sequential') === 0) {
            return false;
        }

        try {
            return (bool) $vipsImage->get('vips-sequential');
        } catch (VipsException $e) {
            throw new DriverException('Failed to read the sequential flag of the image', previous: $e);
        }
    }

    /**
     * Render the pipeline behind the given vips image into memory and drop
     * the sequential claim from the result.
     *
     * The rendered image carries the vips-sequential field of its source
     * along. That is a stale claim on an image that serves any request from
     * memory. Left in place, the next check renders the image all over again
     * and every operation chained on top inherits it. The field is removed
     * from a copy of the header, never from the rendered image itself:
     * copyMemory() hands the source back as it is when that one is already
     * in memory, and it may be shared.
     *
     * @throws DriverException
     */
    private static function renderToMemory(VipsImage $vipsImage): VipsImage
    {
        try {
            $rendered = $vipsImage->copyMemory();

            if ($rendered->getType('vips-sequential') === 0) {
                return $rendered;
            }

            $rendered = $rendered->copy();
            $rendered->remove('vips-sequential');
        } catch (VipsException $e) {
            throw new DriverException('Failed to render image pipeline into memory', previous: $e);
        }

        return $rendered;
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

    /**
     * Clone instance
     *
     * Operations on a vips image return new images and the core itself never
     * writes to the one it holds, so sharing it with the clone is fine on
     * its own. What is not is the pipeline behind a decoded image: the
     * decoders open the source for a single sequential pass, and only one of
     * the two images could walk it. While the stash is in place the clone
     * reopens the source instead, a fresh pipeline at no raster cost. That
     * is a header read, or for a buffer a copy of the encoded bytes into
     * memory libvips owns, and it can fail: a file that went away since the
     * decode throws here rather than at the encode. Once the stash is gone
     * the vips image is shared, and if it is still sequential the first of
     * the two images to be evaluated consumes it. Core::ensureInMemory()
     * before cloning renders it once for both.
     *
     * @throws DriverException
     */
    public function __clone(): void
    {
        $this->meta = clone $this->meta;

        if ($this->stashedSource !== null) {
            $this->vipsImage = $this->reopenStashedSource($this->stashedSource);
            $this->chainedOperations = 0;
        }
    }
}
