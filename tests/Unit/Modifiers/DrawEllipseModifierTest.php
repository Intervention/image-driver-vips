<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Geometry\Ellipse;
use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Modifiers\DrawEllipseModifier;
use Intervention\Image\Geometry\Point;

#[CoversClass(DrawEllipseModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\DrawEllipseModifier::class)]
final class DrawEllipseModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->pickColor(14, 14)->toHex());
        $drawable = new Ellipse(10, 10, new Point(14, 14));
        $drawable->setBackgroundColor('b53717');
        $drawable->setBorderColor('ffffff');
        $drawable->setBorderSize(2);
        $image->modify(new DrawEllipseModifier($drawable));
        $this->assertEquals('b53717', $image->pickColor(14, 14)->toHex());
        $this->assertEquals('ffffff', $image->pickColor(10, 10)->toHex());
    }

    public function testApplyAnimated(): void
    {
        $image = (new Driver())->createAnimation(function ($animation) {
            $animation->add($this->getTestResourcePath('trim.png'), .25);
            $animation->add($this->getTestResourcePath('radial.png'), .25);
        });

        $drawable = new Ellipse(10, 10, new Point(14, 14));
        $drawable->setBackgroundColor('b53717');
        $drawable->setBorderColor('ffffff');
        $drawable->setBorderSize(2);
        $image->modify(new DrawEllipseModifier($drawable));

        $this->assertEquals(2, count($image));

        foreach ($image as $frame) {
            $this->assertEquals('b53717', $frame->toImage(new Driver())->pickColor(14, 14)->toHex());
            $this->assertEquals('ffffff', $frame->toImage(new Driver())->pickColor(10, 10)->toHex());
        }
    }
}
