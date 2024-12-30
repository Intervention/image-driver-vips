<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Encoders;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Encoders\HeicEncoder;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HeicEncoder::class)]
final class HeicEncoderTest extends BaseTestCase
{
    public function testEncode(): void
    {
        $image = (new Driver())->createImage(3, 2);
        $encoder = new HeicEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $this->assertMediaType('image/heic', $result);
        $this->assertEquals('image/heic', $result->mimetype());
    }

    public function testEncodeAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $encoder = new HeicEncoder(75);
        $encoder->setDriver(new Driver());
        $result = $encoder->encode($image);
        $encoded = $this->readFilePointer($result->toFilePointer());
        $this->assertSame($image->width(), $encoded->width());
        $this->assertSame($image->height(), $encoded->height());
    }
}
