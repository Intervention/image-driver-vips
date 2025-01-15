<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Modifiers\CoverDownModifier;

#[CoversClass(CoverDownModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\CoverDownModifier::class)]
final class CoverDownModifierTest extends BaseTestCase
{
    public function testModify(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new CoverDownModifier(100, 100, 'center'));
        $this->assertEquals(100, $image->width());
        $this->assertEquals(100, $image->height());
        $this->assertColor(255, 0, 0, 255, $image->pickColor(90, 90));
        $this->assertColor(0, 255, 0, 255, $image->pickColor(65, 70));
        $this->assertColor(0, 0, 255, 255, $image->pickColor(70, 52));
        $this->assertTransparency($image->pickColor(90, 30));
    }

    public function testModifyOddSize(): void
    {
        $image = (new Driver())->createImage(375, 250);
        $image->modify(new CoverDownModifier(240, 90, 'center'));
        $this->assertEquals(240, $image->width());
        $this->assertEquals(90, $image->height());
    }

    public function testModifyAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $image = $image->modify(new CoverDownModifier(15, 15, position: 'center'));
        $this->assertEquals(15, $image->width());
        $this->assertEquals(15, $image->height());

        $this->assertEquals(
            array_map(fn(Color $color): string => $color->toHex(), $image->pickColors(8, 8)->toArray()),
            ['ffa601', 'ffa601', 'ffa601', 'ffa601', '394b63', '394b63', '394b63', '394b63']
        );
    }
}
