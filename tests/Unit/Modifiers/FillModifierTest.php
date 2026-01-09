<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Modifiers\FillModifier;
use Intervention\Image\Geometry\Point;

#[CoversClass(FillModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\FillModifier::class)]
final class FillModifierTest extends BaseTestCase
{
    public function testFloodFillColor(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals('0000ff', $image->colorAt(420, 270)->toHex());
        $this->assertEquals('ff0000', $image->colorAt(540, 400)->toHex());
        $image->modify(new FillModifier(new Color(204, 204, 204), new Point(540, 400)));
        $this->assertEquals('0000ff', $image->colorAt(420, 270)->toHex());
        $this->assertEquals('cccccc', $image->colorAt(540, 400)->toHex());
    }

    public function testFillAllColor(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals('0000ff', $image->colorAt(420, 270)->toHex());
        $this->assertEquals('ff0000', $image->colorAt(540, 400)->toHex());
        $image->modify(new FillModifier(new Color(204, 204, 204)));
        $this->assertEquals('cccccc', $image->colorAt(420, 270)->toHex());
        $this->assertEquals('cccccc', $image->colorAt(540, 400)->toHex());
    }

    public function testFillWithAlpha(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertColor(0, 0, 0, 0, $image->colorAt(460, 40));
        $image->modify(new FillModifier(new Color(204, 204, 204, .2)));
        $this->assertColor(204, 204, 204, 51, $image->colorAt(460, 40));
    }
}
