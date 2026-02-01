<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Modifiers\FillTransparentAreasModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class FillTransparentAreasModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('tile.png');
        $result = $image->modify(new FillTransparentAreasModifier('f00'));
        $this->assertInstanceOf(ImageInterface::class, $result);
        $this->assertColor(180, 224, 0, 255, $result->colorAt(0, 0));
        $this->assertColor(255, 0, 0, 255, $result->colorAt(15, 0));
        $this->assertColor(255, 0, 0, 255, $result->colorAt(0, 15));
        $this->assertColor(68, 81, 96, 255, $result->colorAt(15, 15));
    }

    public function testApplyAnimated(): void
    {
        $image = ImageManager::usingDriver(Driver::class)->createImage(16, 16, function ($animation): void {
            $animation->add($this->getTestResourcePath('red.gif'), .25);
            $animation->add($this->getTestResourcePath('green.gif'), .25);
            $animation->add($this->getTestResourcePath('blue.gif'), .25);
        })->setLoops(5);

        $image->modify(new FillTransparentAreasModifier('f00'));
        $this->assertEquals(3, count($image));
        $this->assertEquals(5, $image->loops());

        // encode to gif and read again to verify animation frame count
        $this->assertEquals(
            3,
            ImageManager::usingDriver(Driver::class)->decode(
                $image->encodeUsingFormat(format: Format::GIF),
            )->count(),
        );
    }
}
