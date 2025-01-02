<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Drivers\Vips\Modifiers\ColorspaceModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ColorspaceModifier::class)]
final class ColorspaceModifierTest extends BaseTestCase
{
    public function testColorChange(): void
    {
        $image = $this->readTestImage('cmyk.jpg');
        $this->assertEquals(CmykColorspace::class, $image->colorspace()::class);
        $image->modify(new ColorspaceModifier(RgbColorspace::class));
        $this->assertEquals(RgbColorspace::class, $image->colorspace()::class);
    }
}
