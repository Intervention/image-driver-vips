<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\ScaleModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ScaleModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\ScaleModifier::class)]
final class ScaleModifierTest extends BaseTestCase
{
    public function testModify(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new ScaleModifier(800));
        $this->assertEquals(800, $image->width());
        $this->assertEquals(600, $image->height());
    }
}
