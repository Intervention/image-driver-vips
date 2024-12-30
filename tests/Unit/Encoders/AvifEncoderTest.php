<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Encoders;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Encoders\AvifEncoder;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AvifEncoder::class)]
final class AvifEncoderTest extends BaseTestCase
{
    public function testEncode(): void
    {
        $image = (new Driver())->createImage(3, 2);
        $encoder = new AvifEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertMediaType('image/avif', $result);
        $this->assertEquals('image/avif', $result->mimetype());
    }

    public function testEncodeAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $encoder = new AvifEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $encoded = $this->readFilePointer($result->toFilePointer());
        $this->assertSame($image->width(), $encoded->width());
        $this->assertSame($image->height(), $encoded->height());
    }
}
