<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Drivers\Vips\Decoders\BinaryImageDecoder;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Modifiers\RotateModifier;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
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
            ['ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000', 'ff0000'],
        );
        $this->assertEquals(
            array_map(fn(Color $color): string => $color->toHex(), $image->colorsAt(12, 12)->toArray()),
            ['ffa601', 'ffa601', 'ffa601', 'ffa601', '394b63', '394b63', '394b63', '394b63'],
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

    /**
     * Regression: sources that decode to three bands without an alpha channel
     * threw `linear: vector must have 1 or 4 elements`. The background color is
     * exported against the three-band image, but the rotation then adds an
     * alpha band and hands the stale three-element vector to similarity().
     *
     * 16-bit PNG/TIFF sources are the practical way to get there: the decoder
     * only normalizes to four bands when the interpretation is SRGB, and a
     * 16-bit source loads as RGB16.
     */
    public function testRotateSixteenBitSource(): void
    {
        $image = (new Driver())->decodeImage(self::sixteenBitPng(), [BinaryImageDecoder::class]);
        $image->modify(new RotateModifier(45, 'fff'));

        $rotated = VipsImage::newFromBuffer($image->encode(new PngEncoder())->toString());

        $this->assertEquals(BandFormat::USHORT, $rotated->format);
        $this->assertTrue($rotated->hasAlpha());

        // the exposed corner has to be opaque white on the 16-bit scale, not
        // the 255 that ColorProcessor::export() hands out
        $this->assertEquals([65535, 65535, 65535, 65535], $rotated->getpoint(0, 0));
    }

    /**
     * The alpha channel of the background color survives the rotation of a
     * three-band source, instead of being dropped when the vector is sized to
     * the bandcount of the image before the alpha band is added.
     */
    public function testRotateSixteenBitSourceKeepsBackgroundAlpha(): void
    {
        $image = (new Driver())->decodeImage(self::sixteenBitPng(), [BinaryImageDecoder::class]);
        $image->modify(new RotateModifier(45, 'ffffff00'));

        $rotated = VipsImage::newFromBuffer($image->encode(new PngEncoder())->toString());

        $this->assertEquals(0, $rotated->getpoint(0, 0)[3]);
    }

    /**
     * Quarter turns take the rot90/rot180/rot270 path, which paints no
     * background and must not gain an alpha band.
     */
    public function testRotateSixteenBitSourceQuarterTurn(): void
    {
        $image = (new Driver())->decodeImage(self::sixteenBitPng(), [BinaryImageDecoder::class]);
        $image->modify(new RotateModifier(90, 'fff'));

        $rotated = VipsImage::newFromBuffer($image->encode(new PngEncoder())->toString());

        $this->assertEquals(BandFormat::USHORT, $rotated->format);
        $this->assertFalse($rotated->hasAlpha());
    }

    /**
     * 16-bit RGB PNG, the source shape reported in the issue: three bands,
     * ushort, RGB16 interpretation.
     *
     * @throws VipsException
     */
    private static function sixteenBitPng(): string
    {
        return VipsImage::black(32, 32, ['bands' => 3])
            ->add(30000)
            ->cast(BandFormat::USHORT)
            ->copy(['interpretation' => Interpretation::RGB16])
            ->writeToBuffer('.png', ['bitdepth' => 16]);
    }
}
