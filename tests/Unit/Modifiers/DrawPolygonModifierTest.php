<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\DrawPolygonModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Geometry\Point;
use Intervention\Image\Geometry\Polygon;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DrawPolygonModifier::class)]
final class DrawPolygonModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->colorAt(14, 14)->toHex());
        $drawable = new Polygon([new Point(10, 10), new Point(40, 10), new Point(40, 40), new Point(10, 40)]);
        $drawable->setBackgroundColor('b53717');
        $drawable->setBorder('0f0', 2);
        $result = $image->modify(new DrawPolygonModifier($drawable));
        $this->assertEquals('b53717', $image->colorAt(25, 25)->toHex());
        $this->assertEquals('b53717', $result->colorAt(25, 25)->toHex());
        $this->assertEquals('00ff00', $image->colorAt(10, 10)->toHex());
        $this->assertEquals('00ff00', $result->colorAt(40, 40)->toHex());
    }

    public function testApplyWithoutBackgroundColor(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('ffa601', $image->colorAt(30, 17)->toHex());
        $drawable = new Polygon([new Point(10, 10), new Point(40, 10), new Point(40, 30)]);
        $drawable->setBorder('fff', 4);
        $image->modify(new DrawPolygonModifier($drawable));
        $this->assertEquals('ffffff', $image->colorAt(19, 10)->toHex()); // border
        $this->assertEquals('ffa601', $image->colorAt(30, 17)->toHex()); // background
    }
}
