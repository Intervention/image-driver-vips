<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\RemoveAnimationModifier;
use Intervention\Image\Drivers\Vips\Modifiers\SliceAnimationModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\ResizeModifier;
use Jcupitt\Vips\Interpretation;
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

    /**
     * A grayscale source goes through the stash like any other. The result
     * has to be the sRGB image the decoder produced, not the grayscale
     * thumbnail libvips makes of the source.
     */
    public function testResizeGrayscaleKeepsTheDecodedColorspace(): void
    {
        $image = $this->readTestImage('grayscale.jpg');
        $this->assertNotNull($image->core()->stashedSource());

        $image->modify(new ResizeModifier(10, 10));

        $this->assertSame(Interpretation::SRGB, $image->core()->native()->interpretation);
        $this->assertSame(4, $image->core()->native()->bands);
        $this->assertColor(242, 242, 242, 255, $image->colorAt(0, 0), 1);
        $this->assertColor(114, 114, 114, 255, $image->colorAt(5, 5), 1);
        $this->assertColor(11, 11, 11, 255, $image->colorAt(9, 9), 1);
    }

    public function testResizeGrayscaleWithAlphaKeepsTheAlpha(): void
    {
        $image = $this->readTestImage('grayscale-alpha.png');
        $this->assertNotNull($image->core()->stashedSource());

        $image->modify(new ResizeModifier(10, 10));

        $this->assertSame(Interpretation::SRGB, $image->core()->native()->interpretation);
        $this->assertSame(4, $image->core()->native()->bands);
        $this->assertColor(137, 137, 137, 128, $image->colorAt(5, 5), 1);
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

    public function testResizeRemovedAnimation(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(20, $image->width());
        $this->assertEquals(15, $image->height());
        $image->modify(new RemoveAnimationModifier());
        $image->modify(new ResizeModifier(200, 100));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(100, $image->height());
    }

    public function testResizeSlicedAnimation(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(20, $image->width());
        $this->assertEquals(15, $image->height());
        $image->modify(new SliceAnimationModifier(0, 1));
        $image->modify(new ResizeModifier(200, 100));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(100, $image->height());
    }
}
