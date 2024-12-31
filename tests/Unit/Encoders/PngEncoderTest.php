<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Encoders;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Encoders\PngEncoder;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Drivers\Vips\Tests\Traits\CanInspectPngFormat;
use Intervention\Image\Interfaces\ImageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PngEncoder::class)]
final class PngEncoderTest extends BaseTestCase
{
    use CanInspectPngFormat;

    public function testEncode(): void
    {
        $image = (new Driver())->createImage(3, 2);
        $encoder = new PngEncoder();
        $result = $encoder->encode($image);
        $this->assertMediaType('image/png', $result);
        $this->assertEquals('image/png', $result->mimetype());
        $this->assertFalse($this->isInterlacedPng($result));
    }

    public function testEncodeInterlaced(): void
    {
        $image = (new Driver())->createImage(3, 2);
        $encoder = new PngEncoder(interlaced: true);
        $result = $encoder->encode($image);
        $this->assertMediaType('image/png', $result);
        $this->assertEquals('image/png', $result->mimetype());
        $this->assertTrue($this->isInterlacedPng($result));
    }

    public function testEncodeAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $encoder = new PngEncoder();
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertImageSize($result, $image->width(), $image->height());
    }

    #[DataProvider('indexedDataProvider')]
    public function testEncoderIndexed(ImageInterface $image, PngEncoder $encoder, string $result): void
    {
        $this->assertEquals(
            $result,
            $this->pngColorType($encoder->encode($image)),
        );
    }

    public static function indexedDataProvider(): \Generator
    {
        yield [
            (new Driver())->createImage(3, 2), // new
            new PngEncoder(indexed: false),
            'truecolor-alpha',
        ];
        yield [
            (new Driver())->createImage(3, 2), // new
            new PngEncoder(indexed: true),
            'indexed',
        ];
        yield [
            (new Driver())->createImage(3, 2)->fill('ccc'), // new grayscale
            new PngEncoder(indexed: true),
            'indexed',
        ];
        yield [
            static::readTestImage('circle.png'), // truecolor-alpha
            new PngEncoder(indexed: false),
            'truecolor-alpha',
        ];
        yield [
            static::readTestImage('circle.png'), // indexedcolor-alpha
            new PngEncoder(indexed: true),
            'indexed',
        ];
        yield [
            static::readTestImage('tile.png'), // indexed
            new PngEncoder(indexed: false),
            'truecolor-alpha',
        ];
        yield [
            static::readTestImage('tile.png'), // indexed
            new PngEncoder(indexed: true),
            'indexed',
        ];
        yield [
            static::readTestImage('test.jpg'), // jpeg
            new PngEncoder(indexed: false),
            'truecolor', // should be 'truecolor-alpha' but there seems to be no way to force this with vips
        ];
        yield [
            static::readTestImage('test.jpg'), // jpeg
            new PngEncoder(indexed: true),
            'indexed',
        ];
    }
}
