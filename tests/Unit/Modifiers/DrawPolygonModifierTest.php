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
        $this->assertEquals('00aef0', $image->pickColor(14, 14)->toHex());
        $drawable = new Polygon([new Point(10, 10), new Point(40, 10), new Point(40, 40), new Point(10, 40)]);
        $drawable->setBackgroundColor('b53717');
        $drawable->setBorder('0f0', 2);
        $result = $image->modify(new DrawPolygonModifier($drawable));
        $this->assertEquals('b53717', $image->pickColor(25, 25)->toHex());
        $this->assertEquals('b53717', $result->pickColor(25, 25)->toHex());
        $this->assertEquals('00ff00', $image->pickColor(10, 10)->toHex());
        $this->assertEquals('00ff00', $result->pickColor(40, 40)->toHex());
    }
}
