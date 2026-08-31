<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Modifiers\TrimModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Geometry\Point;
use Intervention\Image\Geometry\Rectangle;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Modifiers\DrawRectangleModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TrimModifier::class)]
final class TrimModifierTest extends BaseTestCase
{
    public function testTrim(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());
        $image->modify(new TrimModifier());
        $this->assertEquals(28, $image->width());
        $this->assertEquals(28, $image->height());
    }

    public function testTrimApple(): void
    {
        $image = $this->readTestImage('apple.jpg');
        $image->modify(new TrimModifier());
        $this->assertEquals(81, $image->width());
        $this->assertEquals(91, $image->height());
    }

    public function testTrimGradient(): void
    {
        $image = $this->readTestImage('radial.png');
        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());
        $image->modify(new TrimModifier(50));
        $this->assertEquals(37, $image->width());
        $this->assertEquals(37, $image->height());
    }

    public function testTrimHighTolerance(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());
        $image->modify(new TrimModifier(1000000));
        $this->assertEquals(1, $image->width());
        $this->assertEquals(1, $image->height());
    }

    public function testTrimAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->expectException(NotSupportedException::class);
        $image->modify(new TrimModifier());
    }

    public function testTrimWithDifferentCornerColors(): void
    {
        $image = $this->cornerTestImage();
        $this->assertEquals('ffffff', $image->colorAt(0, 0)->toHex());
        $this->assertEquals('ff0000', $image->colorAt(119, 119)->toHex());

        $image->modify(new TrimModifier());

        $this->assertEquals(70, $image->width());
        $this->assertEquals(70, $image->height());
        $this->assertEquals('ff0000', $image->colorAt(0, 0)->toHex());
    }

    /**
     * Create a 120x120 image with a white border area and a red block in the
     * lower right corner, so that three corners are white and one is red.
     */
    private function cornerTestImage(): ImageInterface
    {
        $image = ImageManager::usingDriver(Driver::class)->createImage(120, 120);

        $background = new Rectangle(120, 120, new Point(0, 0));
        $background->setBackgroundColor('ffffff');
        $image->modify(new DrawRectangleModifier($background));

        $block = new Rectangle(70, 70, new Point(50, 50));
        $block->setBackgroundColor('ff0000');
        $image->modify(new DrawRectangleModifier($block));

        return $image;
    }
}
