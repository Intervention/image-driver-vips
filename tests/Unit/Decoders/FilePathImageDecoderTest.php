<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Decoders;

use Intervention\Image\Drivers\Vips\Analyzers\HeightAnalyzer;
use Intervention\Image\Drivers\Vips\Modifiers\ResizeModifier;
use Intervention\Image\Modifiers\BlurModifier;
use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Drivers\Vips\Decoders\FilePathImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Image;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(\Intervention\Image\Drivers\Vips\Decoders\FilePathImageDecoder::class)]
final class FilePathImageDecoderTest extends BaseTestCase
{
    protected FilePathImageDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new FilePathImageDecoder();
        $this->decoder->setDriver(new Driver());
    }

    #[DataProvider('filePathsProvider')]
    public function testDecode(string $path, bool $result): void
    {
        if ($result === false) {
            $this->expectException(DecoderException::class);
        }

        $result = $this->decoder->decode($path);

        if ($result) {
            $this->assertInstanceOf(Image::class, $result);
        }
    }

    public function testDecodeWithSequentialAccess(): void
    {
        $image = $this->decoder->decode(self::getTestResourcePath('trim.png'));

        // run more than 1 operation to test sequential mode
        $image->pickColor(14, 14)->toHex();
        $image->modify(new BlurModifier(30));
        $image->modify(new ResizeModifier(10, 10));
        $image->pickColor(7, 7)->toHex();
        $this->assertInstanceOf(Image::class, $image);

        $image = $this->decoder->decode(self::getTestResourcePath('trim.png'));
        $image->modify(new BlurModifier(30));
        $image->modify(new ResizeModifier(10, 10));

        $analyzer = new HeightAnalyzer();
        $analyzer->setDriver(new Driver());
        $analyzer->analyze($image);
        $this->assertInstanceOf(Image::class, $image);
    }

    public static function filePathsProvider(): array
    {
        return [
            [self::getTestResourcePath('cats.gif'), true],
            [self::getTestResourcePath('animation.gif'), true],
            [self::getTestResourcePath('red.gif'), true],
            [self::getTestResourcePath('green.gif'), true],
            [self::getTestResourcePath('blue.gif'), true],
            [self::getTestResourcePath('gradient.bmp'), true],
            [self::getTestResourcePath('circle.png'), true],
            [self::getTestResourcePath('test.jpg'), true],
            [self::getTestResourcePath('test.jpeg'), true],
            ['no-path', false],
            [str_repeat('x', PHP_MAXPATHLEN + 1), false],
        ];
    }
}
