<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests;

use finfo;
use Intervention\Image\Colors\Rgb\Channels\Alpha;
use Intervention\Image\Colors\Rgb\Channels\Blue;
use Intervention\Image\Colors\Rgb\Channels\Green;
use Intervention\Image\Colors\Rgb\Channels\Red;
use Intervention\Image\Colors\Rgb\Color as RgbColor;
use Intervention\Image\Colors\Rgb\Colorspace;
use Intervention\Image\Drivers\Vips\Decoders\BinaryImageDecoder;
use Intervention\Image\Drivers\Vips\Decoders\FilePathImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\EncodedImage;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\ColorInterface;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use Mockery\Adapter\Phpunit\MockeryTestCase;

abstract class BaseTestCase extends MockeryTestCase
{
    public static function getTestResourcePath($filename = 'test.jpg'): string
    {
        return sprintf('%s/resources/%s', __DIR__, $filename);
    }

    public static function getTestResourceData($filename = 'test.jpg'): string
    {
        return file_get_contents(self::getTestResourcePath($filename));
    }

    public static function readTestImage($filename = 'test.jpg'): Image
    {
        return (new Driver())->specialize(new FilePathImageDecoder())->decode(
            static::getTestResourcePath($filename)
        );
    }

    public function newImage(int $width, int $height, ?array $background = null): VipsImage
    {
        $driver = new Driver();
        $background = $driver->colorProcessor(new Colorspace())->nativeToColor($background ?? [255, 255, 255, 0]);

        return VipsImage::black(1, 1)
            ->add($background->channel(Red::class)->value())
            ->cast(BandFormat::UCHAR)
            ->embed(0, 0, $width, $height, ['extend' => Extend::COPY])
            ->copy(['interpretation' => Interpretation::SRGB])
            ->bandjoin([
                $background->channel(Green::class)->value(),
                $background->channel(Blue::class)->value(),
                $background->channel(Alpha::class)->value(),
            ]);
    }

    /**
     * Assert that given color equals the given color channel values in the given optional tolerance
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $a
     * @param ColorInterface $color
     * @param int $tolerance
     *
     * @throws ExpectationFailedException
     * @return void
     */
    protected function assertColor(int $r, int $g, int $b, int $a, ColorInterface $color, int $tolerance = 0)
    {
        // build errorMessage
        $errorMessage = function (int $r, int $g, $b, int $a, ColorInterface $color): string {
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

        // build color channel value range
        $range = function (int $base, int $tolerance): array {
            return range(max($base - $tolerance, 0), min($base + $tolerance, 255));
        };

        $this->assertContains(
            $color->channel(Red::class)->value(),
            $range($r, $tolerance),
            $errorMessage($r, $g, $b, $a, $color)
        );

        $this->assertContains(
            $color->channel(Green::class)->value(),
            $range($g, $tolerance),
            $errorMessage($r, $g, $b, $a, $color)
        );

        $this->assertContains(
            $color->channel(Blue::class)->value(),
            $range($b, $tolerance),
            $errorMessage($r, $g, $b, $a, $color)
        );

        $this->assertContains(
            $color->channel(Alpha::class)->value(),
            $range($a, $tolerance),
            $errorMessage($r, $g, $b, $a, $color)
        );
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
        try {
            $image = (new Driver())
                ->specialize(new BinaryImageDecoder())
                ->decode(
                    (string) $image
                );
        } catch (RuntimeException) {
            $this->fail('Unable to read image size.');
        }

        $this->assertEquals(
            $image->width(),
            $width,
            'Failed asserting that the detected image width (' . $image->width() . ') is ' . $width . ' pixels.',
        );
        $this->assertEquals(
            $image->height(),
            $height,
            'Failed asserting that the detected image height (' . $image->height() . ') is ' . $height . ' pixels.',
        );
    }
}
