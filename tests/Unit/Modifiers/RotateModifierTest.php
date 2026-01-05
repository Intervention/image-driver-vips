<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\RotateModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RotateModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\RotateModifier::class)]
final class RotateModifierTest extends BaseTestCase
{
    public function testRotate(): void
    {
        $image = $this->readTestImage('test.jpg');
        $this->assertEquals(320, $image->width());
        $this->assertEquals(240, $image->height());

        $image->modify(new RotateModifier(90, 'fff'));
        $this->assertEquals(240, $image->width());
        $this->assertEquals(320, $image->height());

        $image->modify(new RotateModifier(120, 'fff'));
        $this->assertEquals(397, $image->width());
        $this->assertEquals(368, $image->height());
        $this->assertEquals('ffffff', $image->pickColor(10, 10)->toHex());
    }

    public function testRotateAnimated(): void
    {
        $image = (new Driver())->createAnimation(function ($animation): void {
            $animation->add($this->getTestResourcePath('test.jpg'), .25);
            $animation->add($this->getTestResourcePath('test.jpg'), .25);
        })->setLoops(5);

        $image->modify(new RotateModifier(90, 'fff'));
        $this->assertEquals(240, $image->width());
        $this->assertEquals(320, $image->height());

        $image->modify(new RotateModifier(120, 'fff'));
        $this->assertEquals(2, $image->count());

        foreach ($image as $frame) {
            $this->assertEquals(397, $frame->size()->width());
            $this->assertEquals(368, $image->size()->height());
            $this->assertEquals('ffffff', $frame->toImage(new Driver())->pickColor(10, 10)->toHex());
        }
    }

    public function testRotateGif(): void
    {
        $image = $this->readTestImage('animation.gif');
        $image->modify(new RotateModifier(45, 'f00'));
        $this->assertEquals(25, $image->width());
        $this->assertEquals(25, $image->height());

        $this->assertEquals(
            array_map(fn(Color $color): string => $color->toHex(), $image->pickColors(1, 1)->toArray()),
            ['ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000']
        );
        $this->assertEquals(
            array_map(fn(Color $color): string => $color->toHex(), $image->pickColors(12, 12)->toArray()),
            ['ffa601', 'ffa601', 'ffa601', 'ffa601', '394b63', '394b63', '394b63', '394b63']
        );
    }
}
