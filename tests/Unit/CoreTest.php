<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Frame;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\AnimationException;
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
        $this->expectException(AnimationException::class);
        $this->core->frame(3);
    }

    public function testFrameWithStaticImage(): void
    {
        $black = VipsImage::black(10, 10);
        $this->assertInstanceOf(Frame::class, (new Core($black))->frame(0));
        $this->expectException(AnimationException::class);
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
        $image = (new Driver())->createAnimation(function ($animation): void {
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

    public function testIteratorAggregate(): void
    {
        foreach ($this->core as $frame) {
            $this->assertInstanceOf(Frame::class, $frame);
        }
    }
}
