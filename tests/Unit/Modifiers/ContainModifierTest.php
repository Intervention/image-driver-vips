<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\ContainModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ContainModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\ContainModifier::class)]
final class ContainModifierTest extends BaseTestCase
{
    public function testModifyContain(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new ContainModifier(200, 100, 'ff0'));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(100, $image->height());
        $this->assertColor(255, 255, 0, 255, $image->colorAt(0, 0));
        $this->assertColor(0, 0, 0, 0, $image->colorAt(140, 10));
        $this->assertColor(255, 255, 0, 255, $image->colorAt(175, 10));
    }

    public function testModifyContainPosition(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new ContainModifier(1000, 600, 'FF0000', 'left'));
        $this->assertEquals(1000, $image->width());
        $this->assertEquals(600, $image->height());
        $this->assertColor(0, 0, 255, 255, $image->colorAt(0, 0));
        $this->assertColor(0, 0, 0, 0, $image->colorAt(600, 10));
        $this->assertColor(255, 0, 0, 255, $image->colorAt(900, 10));
    }

    public function testModifyContainWithAlpha(): void
    {
        $image = $this->readTestImage('test.jpg');
        $this->assertEquals(320, $image->width());
        $this->assertEquals(240, $image->height());
        $image->modify(new ContainModifier(800, 200, 'transparent', 'right'));
        $this->assertEquals(800, $image->width());
        $this->assertEquals(200, $image->height());
        $this->assertColor(255, 255, 255, 0, $image->colorAt(0, 0));
        $this->assertColor(254, 168, 0, 255, $image->colorAt(799, 190));
    }

    public function testModifyContainAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(20, $image->width());
        $this->assertEquals(15, $image->height());
        $image->modify(new ContainModifier(100, 20, 'transparent', 'top'));
        $this->assertEquals(100, $image->width());
        $this->assertEquals(20, $image->height());
        $this->assertEquals(8, $image->count());

        foreach ($image as $frame) {
            $this->assertColor(255, 255, 255, 0, $frame->toImage(new Driver())->colorAt(0, 0));
        }
    }

    public function testModifyContainGrayscale(): void
    {
        $image = $this->readTestImage('grayscale.png');
        $this->assertEquals(150, $image->width());
        $this->assertEquals(200, $image->height());
        $image->modify(new ContainModifier(200, 200, 'transparent', 'top'));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(200, $image->height());
        $this->assertColor(255, 255, 255, 0, $image->colorAt(0, 0));
        $this->assertColor(0, 0, 0, 255, $image->colorAt(50, 0));
    }

    public function testModifyContainGrayscaleAlpha(): void
    {
        $image = $this->readTestImage('grayscale-alpha.png');
        $this->assertEquals(256, $image->width());
        $this->assertEquals(256, $image->height());
        $image->modify(new ContainModifier(258, 256, 'transparent', 'top'));
        $this->assertEquals(258, $image->width());
        $this->assertEquals(256, $image->height());
        $this->assertColor(255, 255, 255, 0, $image->colorAt(0, 0));
        $this->assertColor(0, 0, 0, 127, $image->colorAt(1, 0), 1);
    }
}
