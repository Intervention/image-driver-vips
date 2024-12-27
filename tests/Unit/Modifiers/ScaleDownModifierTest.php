<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\ScaleDownModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ScaleDownModifier::class)]
final class ScaleDownModifierTest extends BaseTestCase
{
    public function testScaleDown(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new ScaleDownModifier(600, 600));
        $this->assertEquals(600, $image->width());
        $this->assertEquals(450, $image->height());
    }

    public function testScaleDownByHeight(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new ScaleDownModifier(height: 600));
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
    }
}
