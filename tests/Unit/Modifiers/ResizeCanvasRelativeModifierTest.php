<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\RemoveAnimationModifier;
use Intervention\Image\Drivers\Vips\Modifiers\ResizeCanvasRelativeModifier;
use Intervention\Image\Drivers\Vips\Modifiers\SliceAnimationModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResizeCanvasRelativeModifier::class)]
final class ResizeCanvasRelativeModifierTest extends BaseTestCase
{
    public function testModify(): void
    {
        $image = $this->createTestImage(1, 1);
        $this->assertEquals(1, $image->width());
        $this->assertEquals(1, $image->height());
        $image->modify(new ResizeCanvasRelativeModifier(2, 2, 'ff0', 'center'));
        $this->assertEquals(3, $image->width());
        $this->assertEquals(3, $image->height());
        $this->assertColor(255, 255, 0, 255, $image->colorAt(0, 0));
        $this->assertColor(255, 0, 0, 255, $image->colorAt(1, 1));
        $this->assertColor(255, 255, 0, 255, $image->colorAt(2, 2));
    }

    public function testModifyWithTransparency(): void
    {
        $image = $this->readTestImage('tile.png');
        $this->assertEquals(16, $image->width());
        $this->assertEquals(16, $image->height());
        $image->modify(new ResizeCanvasRelativeModifier(2, 2, 'ff0', 'center'));
        $this->assertEquals(18, $image->width());
        $this->assertEquals(18, $image->height());
        $this->assertColor(255, 255, 0, 255, $image->colorAt(0, 0));
        $this->assertColor(180, 224, 0, 255, $image->colorAt(1, 1));
        $this->assertColor(180, 224, 0, 255, $image->colorAt(2, 2));
        $this->assertColor(255, 255, 0, 255, $image->colorAt(17, 17));
        $this->assertTransparency($image->colorAt(12, 1));
    }

    public function testModifyRemovedAnimation(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(20, $image->width());
        $this->assertEquals(15, $image->height());
        $image->modify(new RemoveAnimationModifier());
        $image->modify(new ResizeCanvasRelativeModifier(10, 5, 'ff0', 'center'));
        $this->assertEquals(30, $image->width());
        $this->assertEquals(20, $image->height());
    }

    public function testModifySlicedAnimation(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(20, $image->width());
        $this->assertEquals(15, $image->height());
        $image->modify(new SliceAnimationModifier(0, 1));
        $image->modify(new ResizeCanvasRelativeModifier(10, 5, 'ff0', 'center'));
        $this->assertEquals(30, $image->width());
        $this->assertEquals(20, $image->height());
    }
}
