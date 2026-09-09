<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Decoders\FilePathImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Frame;
use Intervention\Image\Drivers\Vips\Source\BufferSource;
use Intervention\Image\Drivers\Vips\Source\PathSource;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\AnimationFactoryInterface;
use Intervention\Image\Interfaces\CoreInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Jcupitt\Vips\Config;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Core::class)]
class CoreTest extends BaseTestCase
{
    protected Core $core;

    protected function setUp(): void
    {
        $red = $this->vipsImage(10, 10, [255, 0, 0]);
        $green = $this->vipsImage(10, 10, [0, 255, 0]);
        $blue = $this->vipsImage(10, 10, [0, 0, 255]);

        $frames = [$red, $green, $blue];
        $animation = VipsImage::arrayjoin($frames, ['across' => 1]);

        $delay = array_fill(0, count($frames), 300);

        $animation->set('delay', $delay);
        $animation->set('loop', 0);
        $animation->set('page-height', $red->height);
        $animation->set('n-pages', count($frames));

        $this->core = new Core($animation);
    }

    public function testNative(): void
    {
        $this->assertInstanceOf(VipsImage::class, $this->core->native());
    }

    public function testSetNative(): void
    {
        $image1 = $this->vipsImage(10, 10, [255, 0, 0]);
        $core = new Core($image1);
        $image2 = $this->vipsImage(10, 10, [0, 255, 0]);
        $core->setNative($image2);
        $this->assertEquals($image2, $core->native());
    }

    public function testCount(): void
    {
        $this->assertEquals(3, $this->core->count());
    }

    public function testFrame(): void
    {
        $this->assertInstanceOf(Frame::class, $this->core->frame(0));
        $this->assertInstanceOf(Frame::class, $this->core->frame(1));
        $this->assertInstanceOf(Frame::class, $this->core->frame(2));
        $this->expectException(InvalidArgumentException::class);
        $this->core->frame(3);
    }

    public function testFrameWithStaticImage(): void
    {
        $black = VipsImage::black(10, 10);
        $this->assertInstanceOf(Frame::class, (new Core($black))->frame(0));
        $this->expectException(InvalidArgumentException::class);
        (new Core($black))->frame(1);
    }

    public function testAdd(): void
    {
        $image = $this->vipsImage(10, 10, [255, 0, 0]);
        $this->assertEquals(3, $this->core->count());
        $result = $this->core->add(new Frame($image, 300));
        $this->assertEquals(4, $this->core->count());
        $this->assertInstanceOf(Core::class, $result);
    }

    public function testSetGetLoops(): void
    {
        $this->assertEquals(0, $this->core->loops());
        $result = $this->core->setLoops(12);
        $this->assertEquals(12, $this->core->loops());
        $this->assertInstanceOf(Core::class, $result);
    }

    /**
     * A still image carries no loop field. GD and Imagick report 0 there,
     * so does this core.
     */
    public function testLoopsIsZeroForAStillImage(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));

        $this->assertSame(0, $core->loops());
    }

    public function testImageLoopsIsZeroForAStillImage(): void
    {
        $this->assertSame(0, $this->readTestImage('test.jpg')->loops());
        $this->assertSame(0, ImageManager::usingDriver(Driver::class)->createImage(10, 10)->loops());
    }

    /**
     * The field is absent until set, the guard must not shadow the value
     * once it is there.
     */
    public function testSetLoopsOnAStillImageThenGet(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));

        $this->assertSame(7, $core->setLoops(7)->loops());
    }

    public function testHas(): void
    {
        $this->assertTrue($this->core->has(0));
        $this->assertTrue($this->core->has(1));
        $this->assertTrue($this->core->has(2));
        $this->assertFalse($this->core->has(3));
    }

    public function testGet(): void
    {
        $this->assertInstanceOf(Frame::class, $this->core->get(0));
        $this->assertInstanceOf(Frame::class, $this->core->get(1));
        $this->assertInstanceOf(Frame::class, $this->core->get(2));
        $this->assertNull($this->core->get(3));
        $this->assertEquals('foo', $this->core->get(3, 'foo'));
    }

    public function testSlice(): void
    {
        $image = ImageManager::usingDriver(Driver::class)
            ->createImage(16, 16, function (AnimationFactoryInterface $animation): void {
                $animation->add($this->getTestResourcePath('red.gif'), 0);
                $animation->add($this->getTestResourcePath('green.gif'), .25);
                $animation->add($this->getTestResourcePath('blue.gif'), .50);
            });

        $this->assertEquals(3, $image->core()->count());
        $result = $image->core()->slice(1, 2);
        $this->assertEquals(2, $image->core()->count());
        $this->assertEquals(2, $result->count());

        // check delay of sliced frames
        foreach ($image as $i => $frame) {
            $this->assertInstanceOf(FrameInterface::class, $frame);
            $this->assertEquals(($i + 1) * .25, $frame->delay());
        }
    }

    /**
     * frame() extracts with extract_area(), which records the extract origin
     * in the vips image's yoffset. That is not the frame's offset, GD and
     * Imagick report 0 here.
     */
    public function testFrameOffsetIsZero(): void
    {
        $this->assertSame(0, $this->core->frame(1)->offsetLeft());
        $this->assertSame(0, $this->core->frame(1)->offsetTop());
    }

    public function testFrameDelay(): void
    {
        $this->assertEquals(0.3, $this->core->frame(0)->delay());
        $this->assertEquals(0.3, $this->core->frame(1)->delay());
        $this->assertEquals(0.3, $this->core->frame(2)->delay());
    }

    public function testIteratorAggregate(): void
    {
        foreach ($this->core as $frame) {
            $this->assertInstanceOf(Frame::class, $frame);
        }
    }

    public function testStashedSourceIsNullByDefault(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));

        $this->assertNull($core->stashedSource());
    }

    public function testSetStashedSourcePathThenGet(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));
        $stash = new PathSource('/tmp/foo.jpg', 'n=-1');
        $core->setStashedSource($stash);

        $this->assertSame($stash, $core->stashedSource());
    }

    public function testSetStashedSourceBufferThenGet(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));
        $stash = new BufferSource('binary-bytes-here');
        $core->setStashedSource($stash);

        $this->assertSame($stash, $core->stashedSource());
    }

    public function testSetNativeClearsStashedSource(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));
        $core->setStashedSource(new PathSource('/tmp/foo.jpg'));
        $this->assertNotNull($core->stashedSource());

        $core->setNative($this->vipsImage(20, 20, [0, 255, 0]));

        $this->assertNull($core->stashedSource());
    }

    public function testEmptyClearsTheStashedSource(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));
        $core->setStashedSource(new PathSource($this->getTestResourcePath('test.jpg')));

        $core->empty();

        $this->assertNull($core->stashedSource());
    }

    /**
     * Left in place, the stash would let a clone bring the emptied source back.
     */
    public function testCloneOfEmptiedCoreIsEmpty(): void
    {
        $image = $this->readTestImage('test.jpg');
        $image->core()->empty();

        $clone = clone $image;

        $this->assertSame(1, $clone->core()->native()->width);
        $this->assertSame(1, $clone->core()->native()->height);
    }

    public function testMetaStrippedIsFalseByDefault(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));

        $this->assertFalse($core->metaStripped());
    }

    public function testSetMetaStrippedThenGet(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));

        $this->assertTrue($core->setMetaStripped()->metaStripped());
        $this->assertFalse($core->setMetaStripped(false)->metaStripped());
    }

    /**
     * Unlike the stashed source, the flag has to survive setNative() so it
     * still reaches the encoder after any modifier that runs after the strip.
     */
    public function testSetNativeKeepsMetaStripped(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));
        $core->setMetaStripped();

        $core->setNative($this->vipsImage(20, 20, [0, 255, 0]));

        $this->assertTrue($core->metaStripped());
    }

    public function testCloneKeepsMetaStripped(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));
        $core->setMetaStripped();

        $this->assertTrue((clone $core)->metaStripped());
    }

    public function testCloneGetsItsOwnMetaCollection(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));
        $clone = clone $core;

        $clone->meta()->set('foo', 'bar');

        $this->assertFalse($core->meta()->has('foo'));
    }

    /**
     * The decoders open the source for a single sequential pass. A clone that
     * shared that pipeline would leave only one of the two images encodable,
     * the other fails with an out of order read.
     */
    public function testCloneOfDecodedImageEncodesAlongsideTheOriginal(): void
    {
        $image = ImageManager::usingDriver(Driver::class)->decodeBinary($this->getTestResourceData('test.jpg'));
        $clone = clone $image;

        $encodedClone = $clone->encodeUsingFileExtension('jpg');
        $encodedImage = $image->encodeUsingFileExtension('jpg');

        $this->assertSame((string) $encodedClone, (string) $encodedImage);
    }

    public function testCloneOfImageDecodedFromPathEncodesAlongsideTheOriginal(): void
    {
        $image = $this->readTestImage('test.jpg');
        $clone = clone $image;

        $encodedClone = $clone->encodeUsingFileExtension('jpg');
        $encodedImage = $image->encodeUsingFileExtension('jpg');

        $this->assertSame((string) $encodedClone, (string) $encodedImage);
    }

    /**
     * The decoder adds an alpha band to a 3-band sRGB source, the clone has
     * to carry it too.
     */
    public function testCloneOfDecodedImageKeepsTheAlphaBand(): void
    {
        $image = $this->readTestImage('test.jpg');
        $clone = clone $image;

        $this->assertSame(4, $image->core()->native()->bands);
        $this->assertSame(4, $clone->core()->native()->bands);
    }

    /**
     * The decoder converts a grayscale source to sRGB. The clone has to come
     * back the same way, and encodable alongside the original.
     */
    #[DataProvider('grayscaleSourcesProvider')]
    public function testCloneOfDecodedGrayscaleImageEncodesAlongsideTheOriginal(string $filename): void
    {
        $image = $this->readTestImage($filename);
        $clone = clone $image;

        $this->assertSame(Interpretation::SRGB, $clone->core()->native()->interpretation);
        $this->assertSame(4, $clone->core()->native()->bands);

        $encodedClone = $clone->encodeUsingFileExtension('png');
        $encodedImage = $image->encodeUsingFileExtension('png');

        $this->assertSame((string) $encodedClone, (string) $encodedImage);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function grayscaleSourcesProvider(): array
    {
        return [
            'jpeg' => ['grayscale.jpg'],
            'png' => ['grayscale.png'],
            'png with alpha' => ['grayscale-alpha.png'],
        ];
    }

    public function testCloneOfDecodedGrey16ImageEncodesAlongsideTheOriginal(): void
    {
        $bytes = VipsImage::black(8, 8)
            ->add(30000)
            ->cast(BandFormat::USHORT)
            ->copy(['interpretation' => Interpretation::GREY16])
            ->writeToBuffer('.png');
        $this->assertSame(Interpretation::GREY16, VipsImage::newFromBuffer($bytes)->interpretation);

        $image = ImageManager::usingDriver(Driver::class)->decodeBinary($bytes);
        $clone = clone $image;

        $this->assertSame($image->core()->native()->interpretation, $clone->core()->native()->interpretation);
        $this->assertSame($image->core()->native()->bands, $clone->core()->native()->bands);

        $encodedClone = $clone->encodeUsingFileExtension('png');
        $encodedImage = $image->encodeUsingFileExtension('png');

        $this->assertSame((string) $encodedClone, (string) $encodedImage);
    }

    public function testCloneOfDecodedAnimationKeepsItsFrames(): void
    {
        $image = $this->readTestImage('animation.gif');
        $clone = clone $image;

        $this->assertSame(8, $image->count());
        $this->assertSame(8, $clone->count());
        // n-pages counts the pages of the file whether they were loaded or
        // not, the height is what tells the frames apart
        $this->assertSame($image->core()->native()->height, $clone->core()->native()->height);
    }

    public function testCloneOfAnimationDecodedFromBinaryKeepsItsFrames(): void
    {
        $image = ImageManager::usingDriver(Driver::class)->decodeBinary($this->getTestResourceData('animation.gif'));
        $clone = clone $image;

        $this->assertSame(8, $clone->count());
        $this->assertSame($image->core()->native()->height, $clone->core()->native()->height);
    }

    public function testModifyingTheCloneLeavesTheOriginalUntouched(): void
    {
        $image = $this->readTestImage('test.jpg');
        $clone = clone $image;

        $clone->flip();

        $original = (string) $image->encodeUsingFileExtension('png');
        $this->assertSame((string) $this->readTestImage('test.jpg')->encodeUsingFileExtension('png'), $original);
        $this->assertNotSame((string) $clone->encodeUsingFileExtension('png'), $original);
    }

    public function testCloneThrowsWhenTheSourceFileIsGone(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'vips');
        $this->assertNotFalse($path);
        copy($this->getTestResourcePath('test.jpg'), $path);
        $image = (new Driver())->decodeImage($path, [FilePathImageDecoder::class]);
        unlink($path);

        $this->expectException(DriverException::class);
        clone $image;
    }

    /**
     * The clone keeps the stash so the resize modifiers still get their
     * shrink on load path.
     */
    public function testCloneKeepsTheStashedSource(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));
        $stash = new PathSource($this->getTestResourcePath('test.jpg'));
        $core->setStashedSource($stash);

        $this->assertSame($stash, (clone $core)->stashedSource());
    }

    /**
     * Without a stash the clone shares the vips image, which is fine once it
     * has been rendered into memory.
     */
    public function testCloneOfImageRenderedInMemoryEncodesAlongsideTheOriginal(): void
    {
        $image = $this->readTestImage('test.jpg')->flip();
        $this->assertNull($image->core()->stashedSource());
        $clone = clone $image;

        $encodedClone = $clone->encodeUsingFileExtension('jpg');
        $encodedImage = $image->encodeUsingFileExtension('jpg');

        $this->assertSame((string) $encodedClone, (string) $encodedImage);
    }

    public function testSetLoopsOnCloneLeavesTheOriginalUntouched(): void
    {
        $this->assertSame(0, $this->core->loops());
        $clone = clone $this->core;

        $clone->setLoops(7);

        $this->assertSame(7, $clone->loops());
        $this->assertSame(0, $this->core->loops());
    }

    public function testCloneOfDecodedAnimationKeepsTheLoopCountSetOnTheOriginal(): void
    {
        $image = $this->readTestImage('animation.gif');
        $image->setLoops(5);

        $this->assertSame(5, (clone $image)->loops());
    }

    public function testSetNativeLeavesThePipelineLazyBelowTheOperationLimit(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));

        $native = null;
        for ($i = 1; $i < Core::MAX_CHAINED_OPERATIONS; $i++) {
            $native = $this->vipsImage(10, 10, [$i, 0, 0]);
            $core->setNative($native);
        }

        // untouched: the image handed over is the one that comes back
        $this->assertSame($native, $core->native());
    }

    public function testSetNativeRendersThePipelineToMemoryAtTheOperationLimit(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));

        $native = null;
        for ($i = 1; $i <= Core::MAX_CHAINED_OPERATIONS; $i++) {
            $native = $this->vipsImage(10, 10, [$i, 0, 0]);
            $core->setNative($native);
        }

        // rendered: a different instance, holding the last image handed over
        $this->assertNotSame($native, $core->native());
        $this->assertSame($native->writeToBuffer('.png'), $core->native()->writeToBuffer('.png'));
    }

    public function testSetNativeStartsANewBudgetAfterRenderingToMemory(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));

        // spend the whole budget, which renders on the last call
        for ($i = 1; $i <= Core::MAX_CHAINED_OPERATIONS; $i++) {
            $core->setNative($this->vipsImage(10, 10, [$i, 0, 0]));
        }

        // the budget is a period, not a threshold: the next call is lazy again
        $native = $this->vipsImage(10, 10, [1, 0, 0]);
        $core->setNative($native);

        $this->assertSame($native, $core->native());
    }

    public function testChainedModificationsPastTheOperationLimitStillRender(): void
    {
        // Without the render-to-memory guard this chain overruns the libvips
        // worker thread stack and kills the process. Where that happens
        // depends on the platform's thread stack size: 1000 inserts is well
        // past the ceiling measured on macOS, between 150 and 200, and well
        // short of the one on glibc, over 6000. So on Linux CI this asserts
        // only that rendering mid-chain leaves the result alone.
        $manager = ImageManager::usingDriver(Driver::class);

        $image = $manager->createImage(16, 16)->fill('0000ff');
        $watermark = $manager->createImage(4, 4)->fill('ff0000');

        for ($i = 0; $i < 1000; $i++) {
            $image->insert($watermark);
        }

        $this->assertColor(255, 0, 0, 255, $image->colorAt(2, 2));
        $this->assertColor(0, 0, 255, 255, $image->colorAt(10, 10));
    }

    /**
     * arrayjoin() comes out of the libvips operation cache: the same frames
     * give the same image again, and the fields set on it would reach every
     * core built from them.
     */
    public function testCreateFromFramesLeavesAnEarlierCoreAlone(): void
    {
        $this->pinOperationCache();
        $frames = [
            new Frame($this->vipsImage(10, 10, [255, 0, 0]), 0.1),
            new Frame($this->vipsImage(10, 10, [0, 255, 0]), 0.1),
        ];
        $core = Core::createFromFrames($frames, 3);

        $other = Core::createFromFrames($frames, 9);

        $this->assertSame(9, $other->loops());
        $this->assertSame(3, $core->loops());
    }

    /**
     * Same natives, other delays: the frames differ, the arrayjoin() call
     * does not.
     */
    public function testCreateFromFramesWithOtherDelaysLeavesAnEarlierCoreAlone(): void
    {
        $this->pinOperationCache();
        $red = $this->vipsImage(10, 10, [255, 0, 0]);
        $green = $this->vipsImage(10, 10, [0, 255, 0]);
        $core = Core::createFromFrames([new Frame($red, 0.1), new Frame($green, 0.1)]);

        $other = Core::createFromFrames([new Frame($red, 0.5), new Frame($green, 0.5)]);

        $this->assertEquals(0.5, $other->frame(0)->delay());
        $this->assertEquals(0.1, $core->frame(0)->delay());
    }

    /**
     * The extracted area comes out of the operation cache too, a later
     * extraction of the same area would get the fields frame() sets.
     */
    public function testFrameLeavesTheExtractedAreaAlone(): void
    {
        $this->pinOperationCache();
        $native = $this->core->native();
        $height = $native->get('page-height');
        $this->core->frame(1);

        $area = $native->extract_area(0, $height, $native->width, $height);

        $this->assertSame(3, $area->get('n-pages'));
        $this->assertSame([300, 300, 300], $area->get('delay'));
    }

    /**
     * The rendered image inherits the sequential claim of its source. Left
     * there, the next check renders the image all over again.
     */
    public function testEnsureInMemoryDropsTheSequentialClaimOnceRendered(): void
    {
        $core = $this->readTestImage('test.jpg')->core();
        $this->assertInstanceOf(Core::class, $core);
        $this->assertNotSame(0, $core->native()->getType('vips-sequential'));

        Core::ensureInMemory($core);
        $rendered = $core->native();

        $this->assertSame(0, $rendered->getType('vips-sequential'));
        Core::ensureInMemory($core);
        $this->assertSame($rendered, $core->native());
    }

    /**
     * An operation chained on a rendered image inherits its fields. With the
     * claim gone, the chain is left lazy instead of being rendered again.
     */
    public function testEnsureInMemoryDoesNotRenderAgainAfterAnOperation(): void
    {
        $core = $this->readTestImage('test.jpg')->core();
        $this->assertInstanceOf(Core::class, $core);
        Core::ensureInMemory($core);

        $core->setNative($core->native()->flip('horizontal'));
        $flipped = $core->native();
        Core::ensureInMemory($core);

        $this->assertSame(0, $flipped->getType('vips-sequential'));
        $this->assertSame($flipped, $core->native());
    }

    public function testEnsureInMemoryLeavesAnImageWithoutSequentialClaimAlone(): void
    {
        $core = new Core($this->vipsImage(10, 10, [255, 0, 0]));
        $native = $core->native();

        Core::ensureInMemory($core);

        $this->assertSame($native, $core->native());
    }

    public function testEnsureInMemoryRejectsACoreWithoutVipsImage(): void
    {
        $core = $this->createStub(CoreInterface::class);
        $core->method('native')->willReturn('not a vips image');

        $this->expectException(DriverException::class);
        Core::ensureInMemory($core);
    }

    public function testFrameDropsTheSequentialClaimOnceRendered(): void
    {
        $core = $this->readTestImage('animation.gif')->core();
        $this->assertInstanceOf(Core::class, $core);
        $this->assertNotSame(0, $core->native()->getType('vips-sequential'));

        $core->frame(0);

        $this->assertSame(0, $core->native()->getType('vips-sequential'));
    }

    /**
     * The tests on the operation cache rely on libvips handing the same
     * image back for the same call. With the cache switched off they would
     * pass on the very code they guard. 1000 operations is the libvips
     * default.
     */
    private function pinOperationCache(): void
    {
        Config::cacheSetMax(1000);
    }
}
