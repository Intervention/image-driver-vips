<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\ResizeDownModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResizeDownModifier::class)]
final class ResizeDownModifierTest extends BaseTestCase
{
    public function testResizeDown(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new ResizeDownModifier(800, 800));
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());

        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new ResizeDownModifier(400, 300));
        $this->assertEquals(400, $image->width());
        $this->assertEquals(300, $image->height());
    }
}
