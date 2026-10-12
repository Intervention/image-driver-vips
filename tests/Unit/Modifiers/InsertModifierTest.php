<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\AnimationFactory;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Modifiers\InsertModifier;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(InsertModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\InsertModifier::class)]
final class InsertModifierTest extends BaseTestCase
{
    /**
     * Placements of a 10x5 element on a 40x30 image that leave nothing of it
     * on the canvas.
     *
     * @return array<string, array{string, int, int, float}>
     */
    public static function offCanvasProvider(): array
    {
        return [
            'past the right edge' => ['top-left', 40, 0, 1.0],
            'past the bottom edge' => ['top-left', 0, 30, 1.0],
            'past the left edge' => ['top-left', -10, 0, 1.0],
            'past the top edge' => ['top-left', 0, -5, 1.0],
            'pushed out from the bottom-right' => ['bottom-right', 50, 0, 1.0],
            'far from the center' => ['center', 100, 100, 1.0],
            'just past the right edge from the center' => ['center', 25, 0, 1.0],
            'faded, past the right edge' => ['top-left', 40, 0, .5],
        ];
    }

    #[DataProvider('offCanvasProvider')]
    public function testApplyElementWhollyOffTheCanvasLeavesTheImageUntouched(
        string $position,
        int $x,
        int $y,
        float $transparency,
    ): void {
        $image = $this->createTestImage(40, 30);
        $native = $image->core()->native();
        $element = new Image(new Driver(), new Core(self::vipsImage(10, 5, [0, 255, 0, 255])));

        $image->modify(new InsertModifier($element, $x, $y, $position, $transparency));

        $this->assertSame($native, $image->core()->native());
    }

    public function testApplyElementBelowTheFramesOfAnAnimationLeavesTheImageUntouched(): void
    {
        // The frames of an animation are stacked in one tall native image, an
        // element past the bottom of a frame still lies within that image.
        $image = $this->readTestImage('animation.gif');
        $native = $image->core()->native();
        $element = new Image(new Driver(), new Core(self::vipsImage(10, 5, [0, 255, 0, 255])));

        $image->modify(new InsertModifier($element, 0, $image->height()));

        $this->assertSame($native, $image->core()->native());
    }

    /**
     * Placements of a 10x5 element on a 40x30 image that leave a single row
     * or column of it on the canvas, with the pixel it covers and its
     * neighbour inwards, which it must not cover.
     *
     * @return array<string, array{int, int, int, int, int, int}>
     */
    public static function edgeOfCanvasProvider(): array
    {
        return [
            'last column' => [39, 0, 39, 0, 38, 0],
            'last row' => [0, 29, 0, 29, 0, 28],
            'first column' => [-9, 0, 0, 0, 1, 0],
            'first row' => [0, -4, 0, 0, 0, 1],
        ];
    }

    #[DataProvider('edgeOfCanvasProvider')]
    public function testApplyElementOnTheEdgeOfTheCanvasInsertsWhatOverlaps(
        int $x,
        int $y,
        int $coveredX,
        int $coveredY,
        int $uncoveredX,
        int $uncoveredY,
    ): void {
        $image = $this->createTestImage(40, 30);
        $element = new Image(new Driver(), new Core(self::vipsImage(10, 5, [0, 255, 0, 255])));

        $image->modify(new InsertModifier($element, $x, $y));

        $this->assertColor(0, 255, 0, 255, $image->colorAt($coveredX, $coveredY));
        $this->assertColor(255, 0, 0, 255, $image->colorAt($uncoveredX, $uncoveredY));
    }

    public function testColorChange(): void
    {
        $image = $this->readTestImage('test.jpg');
        $this->assertEquals('febc44', $image->colorAt(300, 25)->toHex());
        $image->modify(new InsertModifier($this->getTestResourcePath('circle.png'), 0, 0, 'top-right'));
        $this->assertEquals('32250d', $image->colorAt(300, 25)->toHex());
    }

    public function testColorChangeTransparencyPng(): void
    {
        $image = $this->readTestImage('test.jpg');
        $this->assertEquals('febc44', $image->colorAt(300, 25)->toHex());
        $image->modify(new InsertModifier($this->getTestResourcePath('circle.png'), 0, 0, 'top-right', .5));
        $this->assertColor(152, 112, 40, 255, $image->colorAt(300, 25), tolerance: 1);
        $this->assertColor(255, 202, 107, 255, $image->colorAt(274, 5), tolerance: 1);
    }

    public function testColorChangeTransparencyJpeg(): void
    {
        $image = ImageManager::usingDriver(Driver::class)->createImage(16, 16)->fill('0000ff');
        $this->assertEquals('0000ff', $image->colorAt(10, 10)->toHex());
        $image->modify(new InsertModifier($this->getTestResourcePath('exif.jpg'), transparency: .5));
        $this->assertColor(127, 83, 127, 255, $image->colorAt(10, 10), tolerance: 1);
    }

    public function testColorChangeAnimated(): void
    {
        $image = ImageManager::usingDriver(Driver::class)
            ->createImage(320, 240, function (AnimationFactory $animation): void {
                $animation->add($this->getTestResourcePath('test.jpg'), .25);
                $animation->add($this->getTestResourcePath('test.jpg'), .25);
            })->setLoops(5);

        $image->modify(new InsertModifier($this->getTestResourcePath('circle.png'), 0, 0, 'top-right'));

        foreach ($image as $frame) {
            $this->assertEquals('32250d', $frame->toImage(new Driver())->colorAt(300, 25)->toHex());
        }
    }

    public function testApplyAnimatedElementInsertsItsFirstFrame(): void
    {
        // The frames of an animation are stacked in one tall native image.
        // Only the first frame is inserted, the others must not run down the
        // image below it.
        $image = $this->createTestImage(40, 30);
        $image->modify(new InsertModifier($this->getTestResourcePath('animation.gif')));

        $this->assertEquals('ffa601', $image->colorAt(10, 7)->toHex());

        $below = $image->core()->native()->crop(0, 15, 40, 15);
        $this->assertEquals(0, $below->extract_band(1, ['n' => 2])->max());
        $this->assertEquals(255, $below->extract_band(0)->min());
    }

    public function testApplyAnimatedElementKeepsAStillImageStill(): void
    {
        // The image takes over the fields that only the element has. With
        // the animation fields of the element, a still image would report the
        // frame height and the loop count of the element.
        $image = $this->createTestImage(40, 30);
        $image->modify(new InsertModifier($this->getTestResourcePath('animation.gif')));

        $this->assertSame(30, $image->height());
        $this->assertSame(0, $image->loops());
        $this->assertFalse($image->isAnimated());
        foreach (['page-height', 'n-pages', 'delay', 'loop'] as $field) {
            $this->assertSame(0, $image->core()->native()->getType($field), $field);
        }
    }

    public function testApplyLeavesTheFieldsOfTheElementAlone(): void
    {
        // The first frame of a still element is its own native image, the
        // fields are removed from a copy of it.
        $element = $this->readTestImage('blue.gif');
        $this->assertSame(1, $element->loops());

        $image = $this->createTestImage(40, 30);
        $image->modify(new InsertModifier($element));

        $this->assertSame(1, $element->loops());
    }

    public function testApplyAnimatedElementOnAnAnimatedImage(): void
    {
        $image = ImageManager::usingDriver(Driver::class)
            ->createImage(40, 30, function (AnimationFactory $animation): void {
                $animation->add($this->createTestImage(40, 30), .1);
                $animation->add($this->createTestImage(40, 30), .2);
            })->setLoops(2);

        $image->modify(new InsertModifier($this->getTestResourcePath('animation.gif')));

        $this->assertCount(2, $image);
        $this->assertSame(30, $image->height());
        $this->assertSame(2, $image->loops());
        foreach ($image as $frame) {
            $this->assertEquals('ffa601', $frame->toImage(new Driver())->colorAt(10, 7)->toHex());

            $below = $frame->native()->crop(0, 15, 40, 15);
            $this->assertEquals(0, $below->extract_band(1, ['n' => 2])->max());
            $this->assertEquals(255, $below->extract_band(0)->min());
        }
    }

    public function testApplyAnimatedWithBinaryWatermarkEncodes(): void
    {
        // Regression for: animated base + watermark decoded from binary
        // (sequential libvips loader) caused composite2 to fail with
        // "pngload_buffer: out of order read" when the pipeline was
        // evaluated during encode.
        $image = $this->readTestImage('animation-large.gif');
        $this->assertTrue($image->isAnimated());

        $image->modify(new InsertModifier(
            $this->getTestResourceData('circle.png'),
            0,
            0,
            'top-right',
        ));

        $encoded = $image->encode(new WebpEncoder(quality: 80));
        $this->assertMediaType('image/webp', $encoded);
    }

    public function testApplyThenEncodeTwiceKeepsTheSameResult(): void
    {
        // Regression for: a watermark decoded from a file opens a sequential
        // libvips source, which may only be read once, in order. It stays in
        // the target's pipeline by reference, so encoding the result a second
        // time read that source a second time and failed with "pngload: out
        // of order read".
        $manager = ImageManager::usingDriver(Driver::class);

        $image = $manager->createImage(64, 64)->fill('0000ff');
        $image->insert($manager->decodePath($this->getTestResourcePath('circle.png')));

        $first = (string) $image->encode(new PngEncoder());
        $second = (string) $image->encode(new PngEncoder());

        $this->assertSame($first, $second);
    }

    public function testApplyDecodedWatermarkPastThePipelineRenderLimitEncodes(): void
    {
        // Rendering the pipeline to memory mid-chain is another evaluation of
        // the watermark's sequential source, on top of the one at encode time.
        // Regression for the render limit and the element materialisation
        // pulling against each other.
        $manager = ImageManager::usingDriver(Driver::class);

        $image = $manager->createImage(64, 64)->fill('0000ff');
        $watermark = $manager->decodePath($this->getTestResourcePath('circle.png'));

        for ($i = 0; $i <= Core::MAX_CHAINED_OPERATIONS; $i++) {
            $image->insert($watermark);
        }

        $this->assertMediaType('image/png', $image->encode(new PngEncoder()));
    }

    public function testApplyCmykElementIsConvertedToTheColourSpaceOfTheImage(): void
    {
        $image = $this->createTestImage(16, 16);
        $image->modify(new InsertModifier($this->getTestResourcePath('cmyk.jpg')));

        [$r, $g, $b] = self::cmykPixelInSrgb();
        $this->assertColor($r, $g, $b, 255, $image->colorAt(5, 5), tolerance: 1);
        $this->assertEquals(Interpretation::SRGB, $image->core()->native()->interpretation);
    }

    public function testApplyCmykElementWithTransparency(): void
    {
        $image = $this->createTestImage(16, 16);
        $image->modify(new InsertModifier($this->getTestResourcePath('cmyk.jpg'), transparency: .5));

        [$r, $g, $b] = self::cmykPixelInSrgb();
        $this->assertColor(
            (int) round(($r + 255) / 2),
            (int) round($g / 2),
            (int) round($b / 2),
            255,
            $image->colorAt(5, 5),
            tolerance: 2,
        );
    }

    public function testApplyCmykElementWithAlpha(): void
    {
        $image = $this->createTestImage(16, 16);
        $element = VipsImage::newFromFile($this->getTestResourcePath('cmyk.jpg'))->bandjoin_const(255);
        $image->modify(new InsertModifier(new Image(new Driver(), new Core($element))));

        [$r, $g, $b] = self::cmykPixelInSrgb();
        $this->assertColor($r, $g, $b, 255, $image->colorAt(5, 5), tolerance: 1);
    }

    public function testApplyCmykElementOnACmykImageIsCopiedAsItIs(): void
    {
        $image = new Image(new Driver(), new Core(self::whiteCmykImage(16, 16)));
        $image->modify(new InsertModifier($this->getTestResourcePath('cmyk.jpg'), 2, 2));

        $native = $image->core()->native();
        $this->assertEquals(Interpretation::CMYK, $native->interpretation);
        $this->assertEquals(4, $native->bands);
        $this->assertEquals(
            VipsImage::newFromFile($this->getTestResourcePath('cmyk.jpg'))->getpoint(5, 5),
            $native->getpoint(7, 7),
        );
        $this->assertEquals([0, 0, 0, 0], $native->getpoint(0, 0));
    }

    public function testApplyCmykElementOnACmykImageWithAlphaKeepsItsColourSpace(): void
    {
        $image = new Image(new Driver(), new Core(self::whiteCmykImage(16, 16)->bandjoin_const(255)));
        $image->modify(new InsertModifier($this->getTestResourcePath('cmyk.jpg'), 2, 2));

        $native = $image->core()->native();
        $this->assertEquals(Interpretation::CMYK, $native->interpretation);
        $this->assertEquals(5, $native->bands);
        $this->assertEquals(
            [...VipsImage::newFromFile($this->getTestResourcePath('cmyk.jpg'))->getpoint(5, 5), 255],
            $native->getpoint(7, 7),
        );
    }

    public function testApplyCmykElementWithTransparencyOnACmykImage(): void
    {
        // A faded element is blended with composite2(), which works in sRGB,
        // so the image comes out in sRGB, as with any element that has alpha.
        $image = new Image(new Driver(), new Core(self::whiteCmykImage(16, 16)));
        $image->modify(new InsertModifier($this->getTestResourcePath('cmyk.jpg'), 2, 2, transparency: .5));

        $white = self::whiteCmykImage(1, 1)->colourspace(Interpretation::SRGB)->getpoint(0, 0);
        [$r, $g, $b] = self::cmykPixelInSrgb();
        $this->assertColor(
            (int) (($white[0] + $r) / 2),
            (int) (($white[1] + $g) / 2),
            (int) (($white[2] + $b) / 2),
            255,
            $image->colorAt(7, 7),
            tolerance: 2,
        );
    }

    public function testApplyElementWithoutAlphaOnACmykImageIsConvertedToCmyk(): void
    {
        $image = new Image(new Driver(), new Core(self::whiteCmykImage(16, 16)));
        $element = self::redImage(Interpretation::RGB16, BandFormat::USHORT, 65535);
        $image->modify(new InsertModifier(new Image(new Driver(), new Core($element))));

        $native = $image->core()->native();
        $this->assertEquals(Interpretation::CMYK, $native->interpretation);
        $this->assertEquals(4, $native->bands);
        $this->assertEquals($element->colourspace(Interpretation::CMYK)->getpoint(4, 4), $native->getpoint(4, 4));
    }

    /**
     * @return array<string, array{float, int, int, int}>
     */
    public static function sixteenBitElementProvider(): array
    {
        return [
            'opaque' => [1.0, 255, 0, 0],
            'faded' => [.5, 128, 0, 128],
        ];
    }

    #[DataProvider('sixteenBitElementProvider')]
    public function testApplySixteenBitElementWithoutAlpha(float $transparency, int $r, int $g, int $b): void
    {
        $image = new Image(new Driver(), new Core(self::vipsImage(16, 16, [0, 0, 255, 255])));
        $element = self::redImage(Interpretation::RGB16, BandFormat::USHORT, 65535);

        $image->modify(new InsertModifier(new Image(new Driver(), new Core($element)), transparency: $transparency));

        $this->assertColor($r, $g, $b, 255, $image->colorAt(4, 4), tolerance: 2);
        $this->assertEquals(Interpretation::SRGB, $image->core()->native()->interpretation);
    }

    public function testApplyElementWithoutAlphaOnAMultibandImageIsCopiedAsItIs(): void
    {
        // Multiband is a tag rather than a colour space, there is nothing to
        // convert the element to.
        $image = new Image(new Driver(), new Core(VipsImage::black(16, 16, ['bands' => 4])->cast(BandFormat::UCHAR)));
        $image->modify(new InsertModifier($this->getTestResourcePath('cmyk.jpg')));

        $native = $image->core()->native();
        $this->assertEquals(Interpretation::MULTIBAND, $native->interpretation);
        $this->assertEquals(
            VipsImage::newFromFile($this->getTestResourcePath('cmyk.jpg'))->getpoint(4, 4),
            $native->getpoint(4, 4),
        );
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function redElementProvider(): array
    {
        return [
            'sRGB' => [Interpretation::SRGB, BandFormat::UCHAR, 255],
            'RGB16' => [Interpretation::RGB16, BandFormat::USHORT, 65535],
        ];
    }

    #[DataProvider('redElementProvider')]
    public function testApplyFadedElementOnASixteenBitImageWithoutAlpha(
        string $interpretation,
        string $format,
        int $max,
    ): void {
        // The alpha band given to the element has to be opaque on the scale
        // of the image it was converted to, 65535 here.
        $image = new Image(new Driver(), new Core(
            VipsImage::black(16, 16, ['bands' => 3])
                ->add([0, 0, 65535])
                ->cast(BandFormat::USHORT)
                ->copy(['interpretation' => Interpretation::RGB16]),
        ));
        $element = self::redImage($interpretation, $format, $max);

        $image->modify(new InsertModifier(new Image(new Driver(), new Core($element)), transparency: .5));

        [$r, $g, $b] = $image->core()->native()->getpoint(4, 4);
        $this->assertEqualsWithDelta(32767, $r, 300);
        $this->assertEqualsWithDelta(0, $g, 300);
        $this->assertEqualsWithDelta(32767, $b, 300);
    }

    /**
     * A red 8x8 image without alpha.
     */
    private static function redImage(string $interpretation, string $format, int $max): VipsImage
    {
        return VipsImage::black(8, 8, ['bands' => 3])
            ->add([$max, 0, 0])
            ->cast($format)
            ->copy(['interpretation' => $interpretation]);
    }

    /**
     * A white CMYK image without alpha.
     */
    private static function whiteCmykImage(int $width, int $height): VipsImage
    {
        return VipsImage::black($width, $height, ['bands' => 4])
            ->cast(BandFormat::UCHAR)
            ->copy(['interpretation' => Interpretation::CMYK]);
    }

    /**
     * The pixel at 5, 5 of cmyk.jpg, converted to sRGB through its profile.
     *
     * @return array{int, int, int}
     */
    private static function cmykPixelInSrgb(): array
    {
        $pixel = VipsImage::newFromFile(self::getTestResourcePath('cmyk.jpg'))
            ->colourspace(Interpretation::SRGB)
            ->getpoint(5, 5);

        return [(int) $pixel[0], (int) $pixel[1], (int) $pixel[2]];
    }
}
