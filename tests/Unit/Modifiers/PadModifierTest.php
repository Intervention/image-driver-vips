<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\PadModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PadModifier::class)]
final class PadModifierTest extends BaseTestCase
{
    public function testModify(): void
    {
        $image = $this->readTestImage('blue.gif');
        $this->assertEquals(16, $image->width());
        $this->assertEquals(16, $image->height());
        $image->modify(new PadModifier(30, 20, 'f00'));
        $this->assertEquals(30, $image->width());
        $this->assertEquals(20, $image->height());
        $this->assertColor(255, 0, 0, 255, $image->pickColor(0, 0));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(0, 19));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(29, 0));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(29, 19));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(6, 2));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(7, 1));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(6, 17));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(7, 18));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(23, 1));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(23, 2));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(23, 17));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(23, 18));
        $this->assertColor(100, 100, 255, 255, $image->pickColor(7, 2));
        $this->assertColor(100, 100, 255, 255, $image->pickColor(22, 2));
        $this->assertColor(100, 100, 255, 255, $image->pickColor(7, 17));
        $this->assertColor(100, 100, 255, 255, $image->pickColor(22, 17));
    }

    public function testModifyGrayscale(): void
    {
        $image = $this->readTestImage('grayscale.png');
        $this->assertEquals(150, $image->width());
        $this->assertEquals(200, $image->height());
        $image->modify(new PadModifier(200, 200, 'f00'));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(200, $image->height());
        $this->assertColor(255, 0, 0, 255, $image->pickColor(0, 0));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(0, 199));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(199, 0));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(199, 199));
    }
}
