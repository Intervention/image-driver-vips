<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\GrayscaleModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GrayscaleModifier::class)]
final class GrayscaleModifierTest extends BaseTestCase
{
    public function testColorChange(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertFalse($image->colorAt(0, 0)->isGrayscale());
        $image->modify(new GrayscaleModifier());
        $this->assertTrue($image->colorAt(0, 0)->isGrayscale());
    }
}
