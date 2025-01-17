<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Decoders;

use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Drivers\Vips\Decoders\BinaryImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Modifiers\ResizeModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Image;
use Intervention\Image\Modifiers\BlurModifier;
use stdClass;

final class BinaryImageDecoderTest extends BaseTestCase
{
    protected BinaryImageDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new BinaryImageDecoder();
        $this->decoder->setDriver(new Driver());
    }

    public function testDecodePng(): void
    {
        $image = $this->decoder->decode(file_get_contents($this->getTestResourcePath('tile.png')));
        $this->assertInstanceOf(Image::class, $image);
        $this->assertInstanceOf(RgbColorspace::class, $image->colorspace());
        $this->assertEquals(16, $image->width());
        $this->assertEquals(16, $image->height());
        $this->assertCount(1, $image);
    }

    public function testDecodeGif(): void
    {
        $image = $this->decoder->decode(file_get_contents($this->getTestResourcePath('red.gif')));
        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals(16, $image->width());
        $this->assertEquals(16, $image->height());
        $this->assertCount(1, $image);
    }

    public function testDecodeAnimatedGif(): void
    {
        $image = $this->decoder->decode(file_get_contents($this->getTestResourcePath('cats.gif')));
        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals(75, $image->width());
        $this->assertEquals(50, $image->height());
        $this->assertCount(4, $image);
    }

    public function testDecodeJpegWithExif(): void
    {
        $image = $this->decoder->decode(file_get_contents($this->getTestResourcePath('exif.jpg')));
        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals(16, $image->width());
        $this->assertEquals(16, $image->height());
        $this->assertCount(1, $image);
        $this->assertEquals('Oliver Vogel', $image->exif('IFD0.Artist'));
    }

    public function testDecodeCmykImage(): void
    {
        $image = $this->decoder->decode(file_get_contents($this->getTestResourcePath('cmyk.jpg')));
        $this->assertInstanceOf(Image::class, $image);
        $this->assertInstanceOf(CmykColorspace::class, $image->colorspace());
    }

    public function testDecodeNonString(): void
    {
        $this->expectException(DecoderException::class);
        $this->decoder->decode(new stdClass());
    }

    public function testDecodeWithSequentialAccess(): void
    {
        $image = $this->decoder->decode(file_get_contents($this->getTestResourcePath('trim.png')));

        // run more than 1 operation to test sequential mode
        $image->pickColor(14, 14)->toHex();
        $image->modify(new BlurModifier(30));
        $image->modify(new ResizeModifier(10, 10));
        $image->pickColor(7, 7)->toHex();
        $this->assertInstanceOf(Image::class, $image);
    }
}
