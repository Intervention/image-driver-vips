<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Generator;
use Intervention\Image\Colors\Rgb\Colorspace;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\FileExtension;
use Intervention\Image\Format;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\ColorProcessorInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\MediaType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Driver::class)]
class DriverTest extends BaseTestCase
{
    protected Driver $driver;

    public function setUp(): void
    {
        $this->driver = new Driver();
    }

    public function testId(): void
    {
        $this->assertEquals('vips', $this->driver->id());
    }

    public function testCreateImage(): void
    {
        $image = $this->driver->createImage(3, 2);
        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals(3, $image->width());
        $this->assertEquals(2, $image->height());
        $this->assertEquals(96, $image->resolution()->x());
        $this->assertEquals(96, $image->resolution()->y());
    }

    public function testCreateAnimation(): void
    {
        $image = $this->driver->createAnimation(function ($animation) {
            $animation->add($this->getTestResourcePath('red.gif'), 0);
            $animation->add($this->getTestResourcePath('green.gif'), .25);
        })->setLoops(5);
        $this->assertInstanceOf(ImageInterface::class, $image);

        $this->assertEquals(16, $image->width());
        $this->assertEquals(16, $image->height());
        $this->assertEquals(5, $image->loops());
        $this->assertEquals(2, $image->count());

        foreach ($image as $i => $frame) {
            $this->assertInstanceOf(FrameInterface::class, $frame);
            $this->assertEquals($i * .25, $frame->delay());
        }
    }

    public function testColorProcessor(): void
    {
        $result = $this->driver->colorProcessor(new Colorspace());
        $this->assertInstanceOf(ColorProcessorInterface::class, $result);
    }

    #[DataProvider('supportsDataProvider')]
    public function testSupports(bool $result, mixed $identifier): void
    {
        $this->assertEquals($result, $this->driver->supports($identifier));
    }

    public static function supportsDataProvider(): Generator
    {
        yield [true, Format::JPEG];
        yield [true, MediaType::IMAGE_JPEG];
        yield [true, MediaType::IMAGE_JPG];
        yield [true, FileExtension::JPG];
        yield [true, FileExtension::JPEG];
        yield [true, 'jpg'];
        yield [true, 'jpeg'];
        yield [true, 'image/jpg'];
        yield [true, 'image/jpeg'];

        yield [true, Format::WEBP];
        yield [true, MediaType::IMAGE_WEBP];
        yield [true, MediaType::IMAGE_X_WEBP];
        yield [true, FileExtension::WEBP];
        yield [true, 'webp'];
        yield [true, 'image/webp'];
        yield [true, 'image/x-webp'];

        yield [true, Format::GIF];
        yield [true, MediaType::IMAGE_GIF];
        yield [true, FileExtension::GIF];
        yield [true, 'gif'];
        yield [true, 'image/gif'];

        yield [true, Format::PNG];
        yield [true, MediaType::IMAGE_PNG];
        yield [true, MediaType::IMAGE_X_PNG];
        yield [true, FileExtension::PNG];
        yield [true, 'png'];
        yield [true, 'image/png'];
        yield [true, 'image/x-png'];

        yield [true, Format::AVIF];
        yield [true, MediaType::IMAGE_AVIF];
        yield [true, MediaType::IMAGE_X_AVIF];
        yield [true, FileExtension::AVIF];
        yield [true, 'avif'];
        yield [true, 'image/avif'];
        yield [true, 'image/x-avif'];

        yield [true, Format::BMP];
        yield [true, FileExtension::BMP];
        yield [true, MediaType::IMAGE_BMP];
        yield [true, MediaType::IMAGE_MS_BMP];
        yield [true, MediaType::IMAGE_X_BITMAP];
        yield [true, MediaType::IMAGE_X_BMP];
        yield [true, MediaType::IMAGE_X_MS_BMP];
        yield [true, MediaType::IMAGE_X_WINDOWS_BMP];
        yield [true, MediaType::IMAGE_X_WIN_BITMAP];
        yield [true, MediaType::IMAGE_X_XBITMAP];
        yield [true, 'bmp'];
        yield [true, 'image/bmp'];
        yield [true, 'image/ms-bmp'];
        yield [true, 'image/x-bitmap'];
        yield [true, 'image/x-bmp'];
        yield [true, 'image/x-ms-bmp'];
        yield [true, 'image/x-windows-bmp'];
        yield [true, 'image/x-win-bitmap'];
        yield [true, 'image/x-xbitmap'];

        yield [true, Format::TIFF];
        yield [true, MediaType::IMAGE_TIFF];
        yield [true, FileExtension::TIFF];
        yield [true, FileExtension::TIF];
        yield [true, 'tif'];
        yield [true, 'tiff'];
        yield [true, 'image/tiff'];

        yield [true, Format::JP2];
        yield [true, MediaType::IMAGE_JP2];
        yield [true, MediaType::IMAGE_JPX];
        yield [true, MediaType::IMAGE_JPM];
        yield [true, FileExtension::TIFF];
        yield [true, FileExtension::TIF];
        yield [true, FileExtension::JP2];
        yield [true, FileExtension::J2K];
        yield [true, FileExtension::JPF];
        yield [true, FileExtension::JPM];
        yield [true, FileExtension::JPG2];
        yield [true, FileExtension::J2C];
        yield [true, FileExtension::JPC];
        yield [true, FileExtension::JPX];
        yield [true, 'jp2'];
        yield [true, 'j2k'];
        yield [true, 'jpf'];
        yield [true, 'jpm'];
        yield [true, 'jpg2'];
        yield [true, 'j2c'];
        yield [true, 'jpc'];
        yield [true, 'jpx'];

        yield [true, Format::HEIC];
        yield [true, MediaType::IMAGE_HEIC];
        yield [true, MediaType::IMAGE_HEIF];
        yield [true, FileExtension::HEIC];
        yield [true, FileExtension::HEIF];
        yield [true, 'heic'];
        yield [true, 'heif'];
        yield [true, 'image/heic'];
        yield [true, 'image/heif'];

        yield [false, 'tga'];
        yield [false, 'image/tga'];
        yield [false, 'image/x-targa'];
        yield [false, 'foo'];
        yield [false, ''];
    }
}
