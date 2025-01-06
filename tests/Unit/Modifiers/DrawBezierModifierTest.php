<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\DrawBezierModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Geometry\Point;
use Intervention\Image\Geometry\Bezier;

#[CoversClass(DrawBezierModifier::class)]
final class DrawBezierModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->pickColor(14, 14)->toHex());
        $drawable = new Bezier([
            new Point(0, 0),
            new Point(15, 0),
            new Point(15, 15),
            new Point(0, 15)
        ]);
        $drawable->setBackgroundColor('b53717');
        $image->modify(new DrawBezierModifier($drawable));
        $this->assertEquals('b53717', $image->pickColor(5, 5)->toHex());
    }
}
