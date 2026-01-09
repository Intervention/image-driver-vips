<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Generator;
use Intervention\Image\Colors\Cmyk\Colorspace as Cmyk;
use Intervention\Image\Colors\Rgb\Colorspace as Rgb;
use Intervention\Image\Colors\Hsv\Colorspace as Hsv;
use Intervention\Image\Colors\Hsl\Colorspace as Hsl;
use Intervention\Image\Colors\Oklab\Colorspace as Oklab;
use Intervention\Image\Colors\Oklch\Colorspace as Oklch;
use Intervention\Image\Colors\Rgb\Color as RgbColor;
use Intervention\Image\Colors\Cmyk\Color as CmykColor;
use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Jcupitt\Vips\Interpretation;
use PHPUnit\Framework\Attributes\DataProvider;

final class ColorProcessorTest extends BaseTestCase
{
    /**
     * @param array<float> $bands
     */
    #[DataProvider('colorToNativeDataProvider')]
    public function testColorToNative(ImageInterface $image, ColorInterface $color, array $bands): void
    {
        $this->assertEquals($bands, (new ColorProcessor($image))->colorToNative($color));
    }

    public static function colorToNativeDataProvider(): Generator
    {
        yield [static::readTestImage('blue.gif'), new RgbColor(255, 0, 55), [255.0, 0.0, 55.0, 255.0]];
        yield [static::readTestImage('blue.gif'), new RgbColor(255, 0, 55, .2), [255.0, 0.0, 55.0, 51.0]];
        yield [static::readTestImage('300dpi.png'), new RgbColor(255, 0, 55), [255.0, 0.0, 55.0, 255.0]];
        yield [static::readTestImage('300dpi.png'), new RgbColor(255, 0, 55, .2), [255.0, 0.0, 55.0, 51.0]];
        yield [static::readTestImage('grayscale.jpg'), new RgbColor(30, 0, 30), [30.0, 0.0, 30.0, 255.0]];
        yield [static::readTestImage('grayscale.jpg'), new RgbColor(30, 0, 30, .2), [30.0, 0.0, 30.0, 51.0]];
        yield [static::readTestImage('grayscale-alpha.png'), new RgbColor(30, 0, 30), [30.0, 0.0, 30.0, 255.0]];
        yield [static::readTestImage('grayscale-alpha.png'), new RgbColor(30, 0, 30, .2), [30.0, 0.0, 30.0, 51.0]];
        yield [static::readTestImage('gradient.bmp'), new RgbColor(255, 0, 55), [255.0, 0.0, 55.0, 255.0]];
        yield [static::readTestImage('gradient.bmp'), new RgbColor(255, 0, 55, .2), [255.0, 0.0, 55.0, 51.0]];
        yield [static::readTestImage('blocks.png'), new RgbColor(255, 0, 55), [255.0, 0.0, 55.0, 255.0]];
        yield [static::readTestImage('blocks.png'), new RgbColor(255, 0, 55, .2), [255.0, 0.0, 55.0, 51.0]];
        yield [static::readTestImage('cmyk.jpg'), new RgbColor(0, 0, 0), [0.0, 0.0, 0.0, 255.0]];
        yield [static::readTestImage('cmyk.jpg'), new RgbColor(0, 0, 0, .2), [0.0, 0.0, 0.0, 255.0]];
        yield [static::readTestImage('blue.gif'), new CmykColor(0, 100, 100, 0), [255.0, 0.0, 0.0, 255.0]];
        yield [static::readTestImage('blue.gif'), new CmykColor(0, 100, 100, 0, .2), [255.0, 0.0, 0.0, 51.0]];
    }

    /**
     * @param array<float> $bands
     */
    #[DataProvider('nativeToColorDataProvider')]
    public function testNativeToColor(ImageInterface $image, array $bands, ColorInterface $color): void
    {
        $this->assertEquals($color, (new ColorProcessor($image))->nativeToColor($bands));
    }

    public static function nativeToColorDataProvider(): Generator
    {
        yield [static::readTestImage('blue.gif'), [255.0], new RgbColor(255, 255, 255, 1)];
        yield [static::readTestImage('blue.gif'), [255.0, 51.], new RgbColor(255, 255, 255, .2)];
        yield [static::readTestImage('blue.gif'), [255.0, 0.0, 55.0], new RgbColor(255, 0, 55, 1)];
        yield [static::readTestImage('blue.gif'), [255.0, 0.0, 55.0, 255.0], new RgbColor(255, 0, 55, 1)];
        yield [static::readTestImage('grayscale-alpha.png'), [255.0], new RgbColor(255, 255, 255, 1)];
        yield [static::readTestImage('grayscale-alpha.png'), [255.0, 51.0], new RgbColor(255, 255, 255, .2)];
        yield [static::readTestImage('grayscale-alpha.png'), [255.0, 0.0, 55.0], new RgbColor(255, 0, 55, 1)];
        yield [static::readTestImage('grayscale-alpha.png'), [255.0, 0.0, 55.0, 51.0], new RgbColor(255, 0, 55, .2)];
        yield [static::readTestImage('cmyk.jpg'), [25.0], new CmykColor(10, 10, 10, 10, 1)];
        yield [static::readTestImage('cmyk.jpg'), [25.0, 51.0], new CmykColor(10, 10, 10, 10, .2)];
        yield [static::readTestImage('cmyk.jpg'), [255.0, 0.0, 127.0, 255.0], new CmykColor(100, 0, 50, 100, 1)];
        yield [static::readTestImage('cmyk.jpg'), [255.0, 0.0, 127.0, 255.0, 51.0], new CmykColor(100, 0, 50, 100, .2)];
    }

    #[DataProvider('interpretationToColorspaceProvider')]
    public function testInterpretationToColorspace(string $interpretation, string $colorspaceClassname): void
    {
        $this->assertInstanceOf($colorspaceClassname, ColorProcessor::interpretationToColorspace($interpretation));
    }

    public static function interpretationToColorspaceProvider(): Generator
    {
        yield [Interpretation::MULTIBAND, Rgb::class];
        yield [Interpretation::B_W, Rgb::class];
        yield [Interpretation::HISTOGRAM, Rgb::class];
        yield [Interpretation::FOURIER, Rgb::class];
        yield [Interpretation::XYZ, Rgb::class];
        yield [Interpretation::LAB, Rgb::class];
        yield [Interpretation::CMYK, Cmyk::class];
        yield [Interpretation::LABQ, Rgb::class];
        yield [Interpretation::RGB, Rgb::class];
        yield [Interpretation::CMC, Rgb::class];
        yield [Interpretation::LCH, Rgb::class];
        yield [Interpretation::LABS, Rgb::class];
        yield [Interpretation::SRGB, Rgb::class];
        yield [Interpretation::HSV, Hsv::class];
        yield [Interpretation::SCRGB, Rgb::class];
        yield [Interpretation::XYZ, Rgb::class];
        yield [Interpretation::RGB16, Rgb::class];
        yield [Interpretation::GREY16, Rgb::class];
        yield [Interpretation::MATRIX, Rgb::class];
    }

    #[DataProvider('colorspaceToInterpretationProvider')]
    public function testColorspaceToInterpretation(string $colorspace, string $interpretation): void
    {
        $this->assertEquals($interpretation, ColorProcessor::colorspaceToInterpretation($colorspace));
    }

    public static function colorspaceToInterpretationProvider(): Generator
    {
        yield [Rgb::class, Interpretation::SRGB];
        yield [Hsv::class, Interpretation::HSV];
        yield [Hsl::class, Interpretation::SRGB];
        yield [Cmyk::class, Interpretation::CMYK];
        yield [Oklch::class, Interpretation::SRGB];
        yield [Oklab::class, Interpretation::SRGB];
    }
}
