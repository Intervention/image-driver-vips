<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Decoders;

use Intervention\Image\Drivers\Vips\Decoders\Base64ImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Image;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(\Intervention\Image\Drivers\Vips\Decoders\Base64ImageDecoder::class)]
class Base64ImageDecoderTest extends BaseTestCase
{
    protected Base64ImageDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new Base64ImageDecoder();
        $this->decoder->setDriver(new Driver());
    }

    public function testDecode(): void
    {
        $result = $this->decoder->decode(
            base64_encode($this->getTestResourceData('blue.gif'))
        );

        $this->assertInstanceOf(Image::class, $result);
    }

    public function testDecoderInvalid(): void
    {
        $this->expectException(ImageDecoderException::class);
        $this->decoder->decode('test');
    }
}
