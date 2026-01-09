<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Decoders;

use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Drivers\Vips\Decoders\DataUriImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Image;
use stdClass;

#[CoversClass(\Intervention\Image\Drivers\Vips\Decoders\DataUriImageDecoder::class)]
final class DataUriImageDecoderTest extends BaseTestCase
{
    protected DataUriImageDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new DataUriImageDecoder();
        $this->decoder->setDriver(new Driver());
    }

    public function testDecode(): void
    {
        $result = $this->decoder->decode(
            sprintf('data:image/jpeg;base64,%s', base64_encode($this->getTestResourceData('blue.gif')))
        );

        $this->assertInstanceOf(Image::class, $result);
    }

    public function testDecoderNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->decoder->decode(new stdClass());
    }

    public function testDecoderInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->decoder->decode('invalid');
    }

    public function testDecoderNonImage(): void
    {
        $this->expectException(ImageDecoderException::class);
        $this->decoder->decode('data:text/plain;charset=utf-8,test');
    }
}
