<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\BlendTransparencyModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Interfaces\ImageInterface;

class BlendTransparencyModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('tile.png');
        $result = $image->modify(new BlendTransparencyModifier('f00'));
        $this->assertInstanceOf(ImageInterface::class, $result);
        $this->assertColor(180, 224, 0, 255, $result->pickColor(0, 0));
        $this->assertColor(255, 0, 0, 255, $result->pickColor(15, 0));
        $this->assertColor(255, 0, 0, 255, $result->pickColor(0, 15));
        $this->assertColor(68, 81, 96, 255, $result->pickColor(15, 15));
    }
}
