<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\TextModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
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
        $font->setAlignment('center');
        $font->setLineHeight(2);
        $image->modify(new TextModifier('ABC', new Point(150, 150), $font));
    }
}
