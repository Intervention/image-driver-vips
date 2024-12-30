<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Encoders;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Encoders\TiffEncoder;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TiffEncoder::class)]
final class TiffEncoderTest extends BaseTestCase
{
    public function testEncode(): void
    {
        $image = (new Driver())->createImage(3, 2);
        $encoder = new TiffEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertMediaType('image/tiff', $result);
        $this->assertEquals('image/tiff', $result->mimetype());
    }

    public function testEncodeAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $encoder = new TiffEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $encoded = $this->readFilePointer($result->toFilePointer());
        $this->assertSame($image->width(), $encoded->width());
        $this->assertSame($image->height(), $encoded->height());
    }
}
