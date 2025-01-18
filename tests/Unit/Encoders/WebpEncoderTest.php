<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Encoders;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Encoders\WebpEncoder;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(WebpEncoder::class)]
final class WebpEncoderTest extends BaseTestCase
{
    public function testEncode(): void
    {
        $image = (new Driver())->createImage(3, 2);
        $encoder = new WebpEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertMediaType('image/webp', $result);
        $this->assertEquals('image/webp', $result->mimetype());
    }

    public function testEncodeAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $encoder = new WebpEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertImageSize($result, $image->width(), $image->height());
    }

    public function testEncoderStripExifData(): void
    {
        $image = $this->readTestImage('exif.jpg');
        $this->assertEquals('Oliver Vogel', $image->exif('IFD0.Artist'));
        $encoder = new WebpEncoder(strip: true);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $image = ImageManager::withDriver(Driver::class)->read($result);
        $this->assertNull($image->exif('IFD0.Artist'));
    }
}
