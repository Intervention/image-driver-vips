<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\ResizeModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResizeModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\ResizeModifier::class)]
final class ResizeModifierTest extends BaseTestCase
{
    public function testResize(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new ResizeModifier(200, 100));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(100, $image->height());
        $this->assertColor(255, 0, 0, 255, $image->pickColor(150, 70));
    }

    public function testResizeAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(20, $image->width());
        $this->assertEquals(15, $image->height());
        $image->modify(new ResizeModifier(10, 10));
        $this->assertEquals(10, $image->width());
        $this->assertEquals(10, $image->height());
    }
}
