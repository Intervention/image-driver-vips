<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Decoders;

use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Drivers\Vips\Decoders\StreamImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Image;

#[CoversClass(StreamImageDecoder::class)]
final class StreamImageDecoderTest extends BaseTestCase
{
    public function testDecode(): void
    {
        $decoder = new StreamImageDecoder();
        $decoder->setDriver(new Driver());
        $fp = fopen($this->getTestResourcePath('test.jpg'), 'r');
        $result = $decoder->decode($fp);
        $this->assertInstanceOf(Image::class, $result);
    }
}
