<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Modifiers\RemoveAnimationModifier;
use Intervention\Image\Drivers\Vips\Modifiers\SliceAnimationModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Jcupitt\Vips\Interpretation;
use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Modifiers\CoverModifier;

#[CoversClass(CoverModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\CoverModifier::class)]
final class CoverModifierTest extends BaseTestCase
{
    public function testModify(): void
    {
        $image = $this->readTestImage('blocks.png');
        $this->assertEquals(640, $image->width());
        $this->assertEquals(480, $image->height());
        $image->modify(new CoverModifier(100, 100, 'center'));
        $this->assertEquals(100, $image->width());
        $this->assertEquals(100, $image->height());
        $this->assertColor(255, 0, 0, 255, $image->colorAt(90, 90));
        $this->assertColor(0, 255, 0, 255, $image->colorAt(65, 70));
        $this->assertColor(0, 0, 255, 255, $image->colorAt(70, 52));
        $this->assertTransparency($image->colorAt(90, 30));
    }

    public function testModifyOddSize(): void
    {
        $image = (new Driver())->createImage(640, 480);
        $image->modify(new CoverModifier(240, 90, 'center'));
        $this->assertEquals(240, $image->width());
        $this->assertEquals(90, $image->height());
    }

    public function testModifyAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $image = $image->modify(new CoverModifier(15, 15, alignment: 'center'));
        $this->assertEquals(15, $image->width());
        $this->assertEquals(15, $image->height());

        $this->assertEquals(
            array_map(fn(Color $color): string => $color->toHex(), $image->colorsAt(8, 8)->toArray()),
            ['ffa601', 'ffa601', 'ffa601', 'ffa601', '394b63', '394b63', '394b63', '394b63'],
        );
    }

    /**
     * A grayscale source goes through the stash like any other. The result
     * has to be the sRGB image the decoder produced, not the grayscale
     * thumbnail libvips makes of the source.
     */
    public function testModifyGrayscaleKeepsTheDecodedColorspace(): void
    {
        $image = $this->readTestImage('grayscale.jpg');
        $this->assertNotNull($image->core()->stashedSource());

        $image->modify(new CoverModifier(10, 10, 'center'));

        $this->assertSame(Interpretation::SRGB, $image->core()->native()->interpretation);
        $this->assertSame(4, $image->core()->native()->bands);
        $this->assertColor(114, 114, 114, 255, $image->colorAt(5, 5), 1);
    }

    public function testModifyCmykSourceProducesValidOutput(): void
    {
        $image = $this->readTestImage('cmyk.jpg');
        $image->modify(new CoverModifier(50, 50, 'center'));

        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());
        $this->assertMediaType('image/jpeg', $image->encode());
    }

    public function testModifyCoverRemovedAnimation(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(20, $image->width());
        $this->assertEquals(15, $image->height());
        $image->modify(new RemoveAnimationModifier());
        $image->modify(new CoverModifier(200, 100, 'center'));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(100, $image->height());
    }

    public function testModifyCoverSlicedAnimation(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(20, $image->width());
        $this->assertEquals(15, $image->height());
        $image->modify(new SliceAnimationModifier(0, 1));
        $image->modify(new CoverModifier(200, 100, 'center'));
        $this->assertEquals(200, $image->width());
        $this->assertEquals(100, $image->height());
    }
}
