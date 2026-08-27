<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Frame;
use Intervention\Image\Drivers\Vips\Source\BufferSource;
use Intervention\Image\Drivers\Vips\Source\PathSource;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\AnimationFactoryInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Jcupitt\Vips\Image as VipsImage;
use PHPUnit\Framework\Attributes\CoversClass;

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
}
