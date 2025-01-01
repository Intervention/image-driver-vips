<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\FlipModifier;
use Intervention\Image\Drivers\Vips\Modifiers\FlopModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FlipModifier::class)]
#[CoversClass(FlopModifier::class)]
final class FlipFlopModifierTest extends BaseTestCase
{
    public function testFlipImage(): void
    {
        $image = $this->readTestImage('tile.png');
        $this->assertEquals('b4e000', $image->pickColor(0, 0)->toHex());
        $image->modify(new FlipModifier());
        $this->assertEquals('00000000', $image->pickColor(0, 0)->toHex());
    }

    public function testFlopImage(): void
    {
        $image = $this->readTestImage('tile.png');
        $this->assertEquals('b4e000', $image->pickColor(0, 0)->toHex());
        $image->modify(new FlopModifier());
        $this->assertEquals('00000000', $image->pickColor(0, 0)->toHex());
    }
}
