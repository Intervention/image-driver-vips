<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\TextModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Geometry\Point;
use Intervention\Image\Typography\Font;

class TextModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('blocks.png');
        $font = new Font($this->getTestResourcePath('test.ttf'));
        $font->setColor('b53517');
        $font->setSize(32);
        $font->setWrapWidth(300);
        $font->setAlignmentHorizontal('center');
        $font->setLineHeight(2);
        $image->modify(new TextModifier('ABC & D', new Point(150, 150), $font));
    }

    public function testApplyWideStroke(): void
    {
        // A stroke of width w is (2w + 1)^2 stamps. Chaining one composite per
        // stamp overran the libvips worker thread stack and killed the process
        // from width 6 up; they are composited in a single operation now.
        $image = $this->readTestImage('blocks.png');
        $font = new Font($this->getTestResourcePath('test.ttf'));
        $font->setColor('b53517');
        $font->setSize(32);
        $font->setStrokeColor('ffffff');
        $font->setStrokeWidth(10);

        $image->modify(new TextModifier('ABC', new Point(150, 150), $font));

        $this->assertMediaType('image/png', $image->encode(new PngEncoder()));
    }
}
