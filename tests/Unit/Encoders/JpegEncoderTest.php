<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Encoders;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Encoders\JpegEncoder;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Drivers\Vips\Tests\Traits\CanDetectProgressiveJpeg;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(JpegEncoder::class)]
final class JpegEncoderTest extends BaseTestCase
{
    use CanDetectProgressiveJpeg;

    public function testEncode(): void
    {
        $image = (new Driver())->createImage(3, 2);
        $encoder = new JpegEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertMediaType('image/jpeg', $result);
        $this->assertEquals('image/jpeg', $result->mimetype());
    }

    public function testEncodeProgressive(): void
    {
        $image = (new Driver())->createImage(3, 2);
        $encoder = new JpegEncoder(progressive: true);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertMediaType('image/jpeg', $result);
        $this->assertEquals('image/jpeg', $result->mimetype());
        $this->assertTrue($this->isProgressiveJpeg($result));
    }

    public function testEncodeAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $encoder = new JpegEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertImageSize($result, $image->width(), $image->height());
    }
}
