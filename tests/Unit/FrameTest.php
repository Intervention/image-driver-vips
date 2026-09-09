<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Frame;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Jcupitt\Vips\Image as VipsImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Frame::class)]
final class FrameTest extends TestCase
{
    public function testConstructor(): void
    {
        $this->assertInstanceOf(Frame::class, $this->testFrame());
    }

    private function testFrame(): FrameInterface
    {
        return new Frame(
            VipsImage::black(3, 2),
        );
    }

    public function testNative(): void
    {
        $this->assertInstanceOf(VipsImage::class, $this->testFrame()->native());
    }

    public function testSetNative(): void
    {
        $this->assertInstanceOf(
            Frame::class,
            $this->testFrame()->setNative(VipsImage::black(3, 2)),
        );
    }

    public function testToImage(): void
    {
        $this->assertInstanceOf(Image::class, $this->testFrame()->toImage(new Driver()));
    }

    public function testSize(): void
    {
        $size = $this->testFrame()->size();
        $this->assertInstanceOf(SizeInterface::class, $size);
        $this->assertEquals(3, $size->width());
        $this->assertEquals(2, $size->height());
    }

    public function testDelay(): void
    {
        $this->assertEquals(0, $this->testFrame()->delay());
    }

    public function testSetDelay(): void
    {
        $this->assertEquals(12, $this->testFrame()->setDelay(12)->delay());
    }

    public function testOffsetIsZeroByDefault(): void
    {
        $frame = $this->testFrame();

        $this->assertSame(0, $frame->offsetLeft());
        $this->assertSame(0, $frame->offsetTop());
    }

    public function testSetOffsetThenGet(): void
    {
        $frame = $this->testFrame()->setOffset(11, 22);

        $this->assertSame(11, $frame->offsetLeft());
        $this->assertSame(22, $frame->offsetTop());
    }

    /**
     * The offset lives on the frame. The vips image is left alone, for a
     * single-frame core it is the core's own. Distinct dimensions on purpose,
     * libvips hands the same image back for identical black() calls.
     */
    public function testSetOffsetLeavesTheVipsImageUntouched(): void
    {
        $vipsImage = VipsImage::black(5, 4);
        $frame = new Frame($vipsImage);
        $fields = $vipsImage->getFields();

        $frame->setOffset(11, 22);

        $this->assertSame($fields, $vipsImage->getFields());
    }
}
