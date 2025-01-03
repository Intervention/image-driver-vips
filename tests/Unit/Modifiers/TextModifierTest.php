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
        $image->modify(new TextModifier('brown fox', new Point(70, 150), $font));
    }
}
