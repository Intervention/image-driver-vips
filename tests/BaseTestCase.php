<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests;

use finfo;
use Intervention\Image\Colors\Rgb\Channels\Alpha;
use Intervention\Image\Colors\Rgb\Channels\Blue;
use Intervention\Image\Colors\Rgb\Channels\Green;
use Intervention\Image\Colors\Rgb\Channels\Red;
use Intervention\Image\Colors\Rgb\Color as RgbColor;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Decoders\FilePathImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\EncodedImage;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\ColorInterface;
use Jcupitt\Vips\Access;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Exception;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    public static function getTestResourcePath(string $filename = 'test.jpg'): string
    {
        return sprintf('%s/resources/%s', __DIR__, $filename);
    }

    public static function getTestResourceData(string $filename = 'test.jpg'): string
    {
        return file_get_contents(self::getTestResourcePath($filename));
    }

    public static function readTestImage(string $filename = 'test.jpg'): Image
    {
        return (new Driver())->handleImageInput(
            static::getTestResourcePath($filename),
            [FilePathImageDecoder::class],
        );
    }

    /**
     * Create new test image with red (ff0000) background.
     *
     * @throws ColorException
     * @throws Exception
     */
    public static function createTestImage(int $width, int $height): Image
    {
        return Image::usingDriver(new Driver())
            ->setCore(new Core(self::vipsImage($width, $height, [255, 0, 0, 255])));
    }

    /**
     * Create new vips image in the given dimensions and background color.
     *
     * @param array<float> $bands
     */
    protected static function vipsImage(int $width, int $height, array $bands): VipsImage
    {
        return VipsImage::black(1, 1)
            ->add(array_slice($bands, 0, 1))
            ->cast(BandFormat::UCHAR)
            ->embed(0, 0, $width, $height, ['extend' => Extend::COPY])
            ->copy(['interpretation' => Interpretation::SRGB])
            ->bandjoin(array_slice($bands, 1));
    }

    /**
     * Assert that given color equals the given color channel values in the given optional tolerance.
     *
     * @throws ExpectationFailedException
     */
    protected function assertColor(int $r, int $g, int $b, int $a, ColorInterface $color, int $tolerance = 0): void
    {
        // build errorMessage
        $errorMessage = function (int $r, int $g, int $b, int $a, ColorInterface $color): string {
            $color = 'rgba(' . implode(', ', [
                $color->channel(Red::class)->value(),
                $color->channel(Green::class)->value(),
                $color->channel(Blue::class)->value(),
                $color->channel(Alpha::class)->value(),
            ]) . ')';

            return implode(' ', [
                'Failed asserting that color',
                $color,
                'equals',
                'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')'
            ]);
        };

        foreach ([Red::class => $r, Green::class => $g, Blue::class => $b, Alpha::class => $a] as $channel => $value) {
            $this->assertThat(
                $color->channel($channel)->value(),
                $this->logicalAnd(
                    $this->greaterThanOrEqual(max($channel::min(), $value - $tolerance)),
                    $this->lessThanOrEqual(min($channel::max(), $value + $tolerance))
                ),
                message: $errorMessage($r, $g, $b, $a, $color)
            );
        }
    }

    protected function assertMediaType(string|array $allowed, string|EncodedImage $input): void
    {
        $detected = (new finfo(FILEINFO_MIME_TYPE))->buffer((string) $input);
        $allowed = is_string($allowed) ? [$allowed] : $allowed;
        $this->assertTrue(
            in_array($detected, $allowed),
            'Detected media type ' . $detected . ' is not in allowed types [' . implode(', ', $allowed) . ']'
        );
    }

    protected function assertTransparency(ColorInterface $color): void
    {
        $this->assertInstanceOf(RgbColor::class, $color);
        $channel = $color->channel(Alpha::class);
        $this->assertEquals(0, $channel->value());
    }

    protected function assertImageSize(string|EncodedImage $image, int $width, int $height): void
    {
        $vipsImage = VipsImage::newFromBuffer((string) $image, 'n=-1', [
            'access' => Access::SEQUENTIAL,
        ]);

        $detectedWidth = $vipsImage->width;
        $detectedHeight = $vipsImage->getType('page-height') === 0 ?
            $vipsImage->height : $vipsImage->get('page-height');

        $this->assertEquals(
            $detectedWidth,
            $width,
            'Failed asserting that the detected image width (' . $detectedWidth . ') is ' . $width . ' pixels.',
        );
        $this->assertEquals(
            $detectedHeight,
            $height,
            'Failed asserting that the detected image height (' . $detectedHeight . ') is ' . $height . ' pixels.',
        );
    }
}
