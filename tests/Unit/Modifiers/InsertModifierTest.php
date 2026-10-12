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
}
