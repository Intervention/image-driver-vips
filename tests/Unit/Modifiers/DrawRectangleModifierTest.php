<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\DrawRectangleModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Geometry\Point;
use Intervention\Image\Geometry\Rectangle;

#[CoversClass(DrawRectangleModifier::class)]
final class DrawRectangleModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->colorAt(14, 14)->toHex());
        $rectangle = new Rectangle(300, 200, new Point(14, 14));
        $rectangle->setBackgroundColor('ffffff');
        $image->modify(new DrawRectangleModifier($rectangle));
        $this->assertEquals('ffffff', $image->colorAt(14, 14)->toHex());
    }

    public function testApplyWithoutBackground(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('ffa601', $image->colorAt(20, 20)->toHex());
        $rectangle = new Rectangle(30, 30, new Point(0, 0));
        $rectangle->setBorder('fff', 5);
        $image->modify(new DrawRectangleModifier($rectangle));
        $this->assertEquals('ffffff', $image->colorAt(0, 0)->toHex()); // border
        $this->assertEquals('ffa601', $image->colorAt(20, 20)->toHex()); // background
    }
}
