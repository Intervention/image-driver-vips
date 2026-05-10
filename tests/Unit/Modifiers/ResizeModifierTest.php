<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\ResizeModifier;
use Intervention\Image\Modifiers\SliceAnimationModifier;
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
        $this->assertColor(255, 0, 0, 255, $image->colorAt(150, 70));
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

    /**
     * Regression: thumbnail_image() carries the input's page-height field
     * over to the result without updating it. HeightAnalyzer prefers
     * page-height when set, so the resized image reported the stale value.
     * Surfaces here via SliceAnimationModifier(0, 1) on an animated source,
     * which leaves page-height set on the now-single-frame native.
     */
    public function testResizeUpdatesPageHeightAfterSliceAnimation(): void
    {
        $image = $this->readTestImage('animation.gif');
        $image->modify(new SliceAnimationModifier(0, 1));
        $image->modify(new ResizeModifier(40, 30));

        $this->assertSame(40, $image->width());
        $this->assertSame(30, $image->height());
    }
}
