<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\CropModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CropModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\CropModifier::class)]
final class CropModifierTest extends BaseTestCase
{
    public function testModifyCrop(): void
    {
        $image = $this->readTestImage('blocks.png');
        $image = $image->modify(new CropModifier(200, 200, 0, 0, 'ffffff', 'bottom-right'));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(200, $image->height());
        $this->assertColor(255, 0, 0, 255, $image->pickColor(5, 5));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(100, 100));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(190, 190));
    }

    public function testModifyCropExtend(): void
    {
        $image = $this->readTestImage('blocks.png');
        $image = $image->modify(new CropModifier(800, 100, -10, -10, 'ff0000', 'top-left'));
        $this->assertEquals(800, $image->width());
        $this->assertEquals(100, $image->height());
        $this->assertColor(255, 0, 0, 255, $image->pickColor(9, 9));
        $this->assertColor(0, 0, 255, 255, $image->pickColor(16, 16));
        $this->assertColor(0, 0, 255, 255, $image->pickColor(445, 16));
        $this->assertTransparency($image->pickColor(460, 16));
    }

    public function testModifyCropExtendWithPosition(): void
    {
        $image = (new Driver())->createImage(50, 50)
            ->fill('ff0000')
            ->crop(100, 25, background: '00f', position: 'center');

        $this->assertEquals(100, $image->width());
        $this->assertEquals(25, $image->height());
        $this->assertEquals('ff0000', $image->pickColor(50, 24)->toHex());
    }

    public function testModifyCropExtendWithAlpha(): void
    {
        $image = $this->readTestImage('blocks.png');
        $image = $image->modify(new CropModifier(800, 379, background: 'transparent'));
        $this->assertEquals(800, $image->width());
        $this->assertEquals(379, $image->height());

        $this->assertTransparency($image->pickColor(799, 378));
    }

    public function testModifyCropSmart(): void
    {
        $image = $this->readTestImage('cats.gif');
        $image = $image->modify(new CropModifier(50, 50, 0, 0, 'ff0000', 'interesting-attention'));
        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());
        $this->assertColor(255, 219, 154, 255, $image->pickColor(25, 25));
    }
}
