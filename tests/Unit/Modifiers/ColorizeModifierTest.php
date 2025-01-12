<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\ColorizeModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ColorizeModifier::class)]
final class ColorizeModifierTest extends BaseTestCase
{
    public function testModify(): void
    {
        $image = $this->readTestImage('tile.png');
        $image = $image->modify(new ColorizeModifier(100, -100, -100));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(5, 5));
        $this->assertColor(255, 0, 0, 255, $image->pickColor(15, 15));
    }
}
