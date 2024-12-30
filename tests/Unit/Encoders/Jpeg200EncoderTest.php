<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Encoders;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Encoders\Jpeg2000Encoder;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\EncodedImage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Jpeg2000Encoder::class)]
final class Jpeg200EncoderTest extends BaseTestCase
{
    public function testEncode(): void
    {
        $image = (new Driver())->createImage(3, 2);
        $encoder = new Jpeg2000Encoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertEquals('image/jp2', $result->mimetype());
        $this->assertTrue($this->isJpeg2000($result), 'Encoding result is not in Jpeg 2000 format.');
    }

    public function testEncodeAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $encoder = new Jpeg2000Encoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $encoded = $this->readFilePointer($result->toFilePointer());
        $this->assertSame($image->width(), $encoded->width());
        $this->assertSame($image->height(), $encoded->height());
    }

    private function isJpeg2000(string|EncodedImage $input): bool
    {
        return 1 === preg_match(
            "/^0000000C6A5020200D0A870A|FF4FFF51/",
            strtoupper(substr(bin2hex((string) $input), 0, 24))
        );
    }
}
