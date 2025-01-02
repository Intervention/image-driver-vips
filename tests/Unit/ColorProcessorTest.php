<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Generator;
use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Colors\Hsv\Colorspace as HsvColorspace;
use Intervention\Image\Colors\Hsl\Colorspace as HslColorspace;
use Intervention\Image\Colors\Rgb\Channels\Alpha;
use Intervention\Image\Colors\Rgb\Channels\Blue;
use Intervention\Image\Colors\Rgb\Channels\Green;
use Intervention\Image\Colors\Rgb\Channels\Red;
use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Colors\Rgb\Colorspace;
use Intervention\Image\Drivers\Vips\ColorProcessor;
use Jcupitt\Vips\Interpretation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ColorProcessorTest extends TestCase
{
    public function testColorToNative(): void
    {
        $processor = new ColorProcessor(new Colorspace());
        $result = $processor->colorToNative(new Color(255, 55, 0, 255));
        $this->assertEquals([255, 55, 0, 255], $result);
    }

    public function testNativeToColor(): void
    {
        $processor = new ColorProcessor(new Colorspace());
        $color = $processor->nativeToColor([255, 55, 0, 255]);
        $this->assertInstanceOf(Color::class, $color);
        $this->assertEquals(255, $color->channel(Red::class)->value());
        $this->assertEquals(55, $color->channel(Green::class)->value());
        $this->assertEquals(0, $color->channel(Blue::class)->value());
        $this->assertEquals(255, $color->channel(Alpha::class)->value());
    }

    #[DataProvider('interpretationToColorspaceProvider')]
    public function testInterpretationToColorspace(string $interpretation, string $colorspaceClassname): void
    {
        $this->assertInstanceOf($colorspaceClassname, ColorProcessor::interpretationToColorspace($interpretation));
    }

    public static function interpretationToColorspaceProvider(): Generator
    {
        yield [Interpretation::MULTIBAND, RgbColorspace::class];
        yield [Interpretation::B_W, RgbColorspace::class];
        yield [Interpretation::HISTOGRAM, RgbColorspace::class];
        yield [Interpretation::FOURIER, RgbColorspace::class];
        yield [Interpretation::XYZ, RgbColorspace::class];
        yield [Interpretation::LAB, RgbColorspace::class];
        yield [Interpretation::CMYK, CmykColorspace::class];
        yield [Interpretation::LABQ, RgbColorspace::class];
        yield [Interpretation::RGB, RgbColorspace::class];
        yield [Interpretation::CMC, RgbColorspace::class];
        yield [Interpretation::LCH, RgbColorspace::class];
        yield [Interpretation::LABS, RgbColorspace::class];
        yield [Interpretation::SRGB, RgbColorspace::class];
        yield [Interpretation::HSV, HsvColorspace::class];
        yield [Interpretation::SCRGB, RgbColorspace::class];
        yield [Interpretation::XYZ, RgbColorspace::class];
        yield [Interpretation::RGB16, RgbColorspace::class];
        yield [Interpretation::GREY16, RgbColorspace::class];
        yield [Interpretation::MATRIX, RgbColorspace::class];
    }

    #[DataProvider('colorspaceToInterpretationProvider')]
    public function testColorspaceToInterpretation(string $colorspace, string $interpretation): void
    {
        $this->assertEquals($interpretation, ColorProcessor::colorspaceToInterpretation($colorspace));
    }

    public static function colorspaceToInterpretationProvider(): Generator
    {
        yield [RgbColorspace::class, Interpretation::SRGB];
        yield [HsvColorspace::class, Interpretation::HSV];
        yield [HslColorspace::class, Interpretation::SRGB];
        yield [CmykColorspace::class, Interpretation::CMYK];
    }
}
