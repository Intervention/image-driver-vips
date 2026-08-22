<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Colors\Rgb\Channels\Alpha;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Modifiers\ColorspaceModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Image;
use Jcupitt\Vips\Interpretation;
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

    public function testColorChangeConvertsPixelData(): void
    {
        // build a cmyk source from a known rgb color, so that converting it
        // back has to land on the original color again
        $native = self::vipsImage(8, 8, [205, 35, 56])->colourspace(Interpretation::CMYK);
        $image = new Image(new Driver(), new Core($native));

        $image->modify(new ColorspaceModifier(RgbColorspace::class));

        $this->assertColor(205, 35, 56, 255, $image->colorAt(0, 0), tolerance: 8);
    }

    public function testColorChangeFromCmykRestoresAlphaBand(): void
    {
        // a cmyk source has no alpha band, so converting it to rgb has to
        // re-add one to keep the bandcount the rest of the pipeline expects
        $image = $this->readTestImage('cmyk.jpg');
        $this->assertEquals(4, $image->core()->native()->bands);

        $image->modify(new ColorspaceModifier(RgbColorspace::class));

        $this->assertEquals(Interpretation::SRGB, $image->core()->native()->interpretation);
        $this->assertEquals(4, $image->core()->native()->bands);
        $this->assertEquals(255, $image->colorAt(0, 0)->channel(Alpha::class)->value());
    }

    public function testColorChangeToCmyk(): void
    {
        $image = $this->createTestImage(8, 8);
        $this->assertEquals(RgbColorspace::class, $image->colorspace()::class);

        $image->modify(new ColorspaceModifier(CmykColorspace::class));

        $this->assertEquals(CmykColorspace::class, $image->colorspace()::class);
        $this->assertEquals(Interpretation::CMYK, $image->core()->native()->interpretation);
    }
}
