<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Drivers\Vips\Decoders\BinaryImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Modifiers\RotateModifier;
use Jcupitt\Vips\Image as VipsImage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RotateModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\RotateModifier::class)]
final class RotateModifierTest extends BaseTestCase
{
    public function testRotate(): void
    {
        $image = $this->readTestImage('test.jpg');
        $this->assertEquals(320, $image->width());
        $this->assertEquals(240, $image->height());

        $image->modify(new RotateModifier(90, 'fff'));
        $this->assertEquals(240, $image->width());
        $this->assertEquals(320, $image->height());

        $image->modify(new RotateModifier(120, 'fff'));
        $this->assertEquals(397, $image->width());
        $this->assertEquals(368, $image->height());
        $this->assertEquals('ffffff', $image->colorAt(10, 10)->toHex());
    }

    public function testRotateAnimated(): void
    {
        $image = ImageManager::usingDriver(Driver::class)->createImage(320, 240, function ($animation): void {
            $animation->add($this->getTestResourcePath('test.jpg'), .25);
            $animation->add($this->getTestResourcePath('test.jpg'), .25);
        })->setLoops(5);

        $image->modify(new RotateModifier(90, 'fff'));
        $this->assertEquals(240, $image->width());
        $this->assertEquals(320, $image->height());

        $image->modify(new RotateModifier(120, 'fff'));
        $this->assertEquals(2, $image->count());

        foreach ($image as $frame) {
            $this->assertEquals(397, $frame->size()->width());
            $this->assertEquals(368, $image->size()->height());
            $this->assertEquals('ffffff', $frame->toImage(new Driver())->colorAt(10, 10)->toHex());
        }
    }

    public function testRotateGif(): void
    {
        $image = $this->readTestImage('animation.gif');
        $image->modify(new RotateModifier(45, 'f00'));
        $this->assertEquals(25, $image->width());
        $this->assertEquals(25, $image->height());

        $this->assertEquals(
            array_map(fn(Color $color): string => $color->toHex(), $image->colorsAt(1, 1)->toArray()),
            ['ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000']
        );
        $this->assertEquals(
            array_map(fn(Color $color): string => $color->toHex(), $image->colorsAt(12, 12)->toArray()),
            ['ffa601', 'ffa601', 'ffa601', 'ffa601', '394b63', '394b63', '394b63', '394b63']
        );
    }

    /**
     * Regression: rotation chained after a sequentially-decoded source threw
     * `pngload_buffer: out of order read` when the encoder finally walked the
     * pipeline. Peer modifiers (Orient, Flip, Trim) call Core::ensureInMemory()
     * before their rotation; this one did not. Reproduces with content large
     * enough to span multiple sequential read chunks - flat fills are read in
     * a single chunk and bypass the constraint.
     */
    public function testRot90AfterSequentialDecodeAllowsEncoding(): void
    {
        $png = VipsImage::gaussnoise(1200, 1200, ['seed' => 42])->writeToBuffer('.png');

        $image = (new Driver())->decodeImage($png, [BinaryImageDecoder::class]);
        $image->modify(new RotateModifier(90, 'fff'));

        $bytes = $image->encode(new JpegEncoder(quality: 75))->toString();
        $this->assertNotEmpty($bytes);
    }

    public function testRotateArbitraryAngleAfterSequentialDecodeAllowsEncoding(): void
    {
        $png = VipsImage::gaussnoise(1200, 1200, ['seed' => 42])->writeToBuffer('.png');

        $image = (new Driver())->decodeImage($png, [BinaryImageDecoder::class]);
        // 45° goes through similarity() rather than rot90; same root cause.
        $image->modify(new RotateModifier(45, 'fff'));

        $bytes = $image->encode(new JpegEncoder(quality: 75))->toString();
        $this->assertNotEmpty($bytes);
    }
}
