<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

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
        $this->assertColor(255, 255, 0, 255, $image->pickColor(0, 0));
        $this->assertColor(0, 0, 0, 0, $image->pickColor(140, 10));
        $this->assertColor(255, 255, 0, 255, $image->pickColor(175, 10));
    }

    public function testModifyContainPosition(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new ContainModifier(1000, 600, 'FF0000', 'left'));
        $this->assertEquals(1000, $image->width());
        $this->assertEquals(600, $image->height());
        $this->assertColor(0, 0, 255, 255, $image->pickColor(0, 0));
        $this->assertColor(0, 0, 0, 0, $image->pickColor(600, 10));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(900, 10));
    }

    public function testModifyContainWithAlpha(): void
    {
        $image = $this->readTestImage('test.jpg');
        $this->assertEquals(320, $image->width());
        $this->assertEquals(240, $image->height());
        $image->modify(new ContainModifier(800, 200, 'transparent', 'right'));
        $this->assertEquals(800, $image->width());
        $this->assertEquals(200, $image->height());
        $this->assertColor(255, 255, 255, 0, $image->pickColor(0, 0));
        $this->assertColor(254, 168, 0, 255, $image->pickColor(799, 190));
    }
}
