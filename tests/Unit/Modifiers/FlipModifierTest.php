<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\FlipModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Direction;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FlipModifier::class)]
final class FlipModifierTest extends BaseTestCase
{
    public function testFlipImageDefault(): void
    {
        $image = $this->readTestImage('tile.png');
        $this->assertEquals('b4e000', $image->colorAt(5, 5)->toHex());
        $this->assertEquals('00000000', $image->colorAt(12, 5)->toHex());
        $this->assertEquals('00000000', $image->colorAt(5, 12)->toHex());
        $this->assertEquals('445160', $image->colorAt(12, 12)->toHex());
        $image->modify(new FlipModifier());
        $this->assertEquals('00000000', $image->colorAt(5, 5)->toHex());
        $this->assertEquals('b4e000', $image->colorAt(12, 5)->toHex());
        $this->assertEquals('445160', $image->colorAt(5, 12)->toHex());
        $this->assertEquals('00000000', $image->colorAt(12, 12)->toHex());
    }

    public function testFlipImageHorizontal(): void
    {
        $image = $this->readTestImage('tile.png');
        $this->assertEquals('b4e000', $image->colorAt(5, 5)->toHex());
        $this->assertEquals('00000000', $image->colorAt(12, 5)->toHex());
        $this->assertEquals('00000000', $image->colorAt(5, 12)->toHex());
        $this->assertEquals('445160', $image->colorAt(12, 12)->toHex());
        $image->modify(new FlipModifier(Direction::HORIZONTAL));
        $this->assertEquals('00000000', $image->colorAt(5, 5)->toHex());
        $this->assertEquals('b4e000', $image->colorAt(12, 5)->toHex());
        $this->assertEquals('445160', $image->colorAt(5, 12)->toHex());
        $this->assertEquals('00000000', $image->colorAt(12, 12)->toHex());
    }

    public function testFlipImageVertical(): void
    {
        $image = $this->readTestImage('tile.png');
        $this->assertEquals('b4e000', $image->colorAt(5, 5)->toHex());
        $this->assertEquals('00000000', $image->colorAt(12, 5)->toHex());
        $this->assertEquals('00000000', $image->colorAt(5, 12)->toHex());
        $this->assertEquals('445160', $image->colorAt(12, 12)->toHex());
        $image->modify(new FlipModifier(Direction::VERTICAL));
        $this->assertEquals('00000000', $image->colorAt(5, 5)->toHex());
        $this->assertEquals('445160', $image->colorAt(12, 5)->toHex());
        $this->assertEquals('b4e000', $image->colorAt(5, 12)->toHex());
        $this->assertEquals('00000000', $image->colorAt(12, 12)->toHex());
    }
}
