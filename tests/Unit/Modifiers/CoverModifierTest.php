<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Modifiers\CoverModifier;

#[CoversClass(CoverModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\CoverModifier::class)]
final class CoverModifierTest extends BaseTestCase
{
    public function testModify(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new CoverModifier(100, 100, 'center'));
        $this->assertEquals(100, $image->width());
        $this->assertEquals(100, $image->height());
        $this->assertColor(255, 0, 0, 255, $image->pickColor(90, 90));
        $this->assertColor(0, 255, 0, 255, $image->pickColor(65, 70));
        $this->assertColor(0, 0, 255, 255, $image->pickColor(70, 52));
        $this->assertTransparency($image->pickColor(90, 30));
    }

    public function testModifyOddSize(): void
    {
        $image = (new Driver())->createImage(640, 480);
        $image->modify(new CoverModifier(240, 90, 'center'));
        $this->assertEquals(240, $image->width());
        $this->assertEquals(90, $image->height());
    }
}