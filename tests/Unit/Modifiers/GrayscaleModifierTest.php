<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Colors\Cmyk\Channels\Key;
use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Drivers\Vips\Decoders\NativeObjectDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Modifiers\GrayscaleModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
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

    public function testColorspaceIsPreserved(): void
    {
        $image = $this->readTestImage('cmyk.jpg');
        $this->assertEquals(Interpretation::CMYK, $image->core()->native()->interpretation);
        $this->assertEquals(CmykColorspace::class, $image->colorspace()::class);

        $image->modify(new GrayscaleModifier());

        // grayscale is a color operation, it has no business moving a cmyk
        // source to another colorspace
        $this->assertEquals(Interpretation::CMYK, $image->core()->native()->interpretation);
        $this->assertEquals(CmykColorspace::class, $image->colorspace()::class);
        $this->assertEquals(4, $image->core()->native()->bands);

        // the grey has to sit in the black channel, the three ink channels
        // carry no color anymore. The exact key value goes through libvips'
        // bundled cmyk profile and the lcms2 it was built against, so it is
        // asserted as a range rather than pinned.
        $color = $image->colorAt(0, 0);
        $this->assertTrue($color->isGrayscale());
        $this->assertGreaterThanOrEqual(35, $color->channel(Key::class)->value());
        $this->assertLessThanOrEqual(45, $color->channel(Key::class)->value());
    }

    public function testInterpretationsWithoutARouteBackFallBackToSrgb(): void
    {
        // multiband is a tag rather than a colorspace, colourspace() can guess
        // it as srgb on the way in but has no route back to it
        $native = VipsImage::black(4, 4, ['bands' => 3])->add([200, 30, 90])->cast(BandFormat::UCHAR);
        $this->assertEquals(Interpretation::MULTIBAND, $native->interpretation);

        $image = (new Driver())->decodeImage($native, [NativeObjectDecoder::class]);
        $image->modify(new GrayscaleModifier());

        $this->assertEquals(Interpretation::SRGB, $image->core()->native()->interpretation);
        $this->assertTrue($image->colorAt(0, 0)->isGrayscale());
    }

    public function testColorspaceIsPreservedForSrgb(): void
    {
        // pinning test, the srgb path already behaved this way, it guards the
        // common case against the change made for cmyk
        $image = $this->readTestImage('trim.png');
        $bands = $image->core()->native()->bands;
        $this->assertEquals(4, $bands);

        $image->modify(new GrayscaleModifier());

        // the srgb path is the common one and has to come out unchanged,
        // bandcount included
        $this->assertEquals(Interpretation::SRGB, $image->core()->native()->interpretation);
        $this->assertEquals($bands, $image->core()->native()->bands);
    }
}
