<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Modifiers\InsertModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InsertModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\InsertModifier::class)]
final class InsertModifierTest extends BaseTestCase
{
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
        $image = ImageManager::usingDriver(Driver::class)->createImage(320, 240, function ($animation): void {
            $animation->add($this->getTestResourcePath('test.jpg'), .25);
            $animation->add($this->getTestResourcePath('test.jpg'), .25);
        })->setLoops(5);

        $image->modify(new InsertModifier($this->getTestResourcePath('circle.png'), 0, 0, 'top-right'));

        foreach ($image as $frame) {
            $this->assertEquals('32250d', $frame->toImage(new Driver())->colorAt(300, 25)->toHex());
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
