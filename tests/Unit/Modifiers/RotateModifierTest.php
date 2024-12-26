<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

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
}
