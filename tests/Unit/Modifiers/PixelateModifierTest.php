<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\ImageManager;
use Intervention\Image\Modifiers\PixelateModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PixelateModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\PixelateModifier::class)]
final class PixelateModifierTest extends BaseTestCase
{
    public function testModify(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->pickColor(0, 0)->toHex());
        $this->assertEquals('00aef0', $image->pickColor(14, 14)->toHex());
        $image->modify(new PixelateModifier(10));
        $color = $image->pickColor(0, 0);
        $this->assertColor(0, 174, 242, 255, $color, 1);

        $color = $image->pickColor(14, 14);
        $this->assertColor(104, 171, 143, 255, $color, 1);
    }

    public function testModifyAnimated(): void
    {
        $image = (new Driver())->createAnimation(function ($animation) {
            $animation->add($this->getTestResourcePath('trim.png'), .25);
            $animation->add($this->getTestResourcePath('radial.png'), .25);
        })->setLoops(5);

        $image->modify(new PixelateModifier(10));
        $this->assertEquals(2, count($image));
        $this->assertEquals(5, $image->loops());

        // encode to gif and read again to verify animation frame count
        $this->assertEquals(2, ImageManager::withDriver(Driver::class)->read($image->toGif())->count());
    }

    public function testModifyWithNonDivisibleDimensions(): void
    {
        // test2.jpg is 900x506 - dimensions not evenly divisible by 20
        // This would cause "extract_area: bad extract area" error before fix
        $image = $this->readTestImage('test2.jpg');
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        $image->modify(new PixelateModifier(20));

        // Verify dimensions are preserved
        $this->assertEquals($originalWidth, $image->width());
        $this->assertEquals($originalHeight, $image->height());
    }
}
