<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\PlaceModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PlaceModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\PlaceModifier::class)]
final class PlaceModifierTest extends BaseTestCase
{
    public function testColorChange(): void
    {
        $image = $this->readTestImage('test.jpg');
        $this->assertEquals('febc44', $image->pickColor(300, 25)->toHex());
        $image->modify(new PlaceModifier($this->getTestResourcePath('circle.png'), 'top-right', 0, 0));
        $this->assertEquals('32250d', $image->pickColor(300, 25)->toHex());
    }

    public function testColorChangeOpacityPng(): void
    {
        $image = $this->readTestImage('test.jpg');
        $this->assertEquals('febc44', $image->pickColor(300, 25)->toHex());
        $image->modify(new PlaceModifier($this->getTestResourcePath('circle.png'), 'top-right', 0, 0, 50));
        $this->assertColor(152, 112, 40, 255, $image->pickColor(300, 25), tolerance: 1);
        $this->assertColor(255, 202, 107, 255, $image->pickColor(274, 5), tolerance: 1);
    }

    public function testColorChangeOpacityJpeg(): void
    {
        $image = (new Driver())->createImage(16, 16)->fill('0000ff');
        $this->assertEquals('0000ff', $image->pickColor(10, 10)->toHex());
        $image->modify(new PlaceModifier($this->getTestResourcePath('exif.jpg'), opacity: 50));
        $this->assertColor(127, 83, 127, 255, $image->pickColor(10, 10), tolerance: 1);
    }

    public function testColorChangeAnimated(): void
    {
        $image = (new Driver())->createAnimation(function ($animation): void {
            $animation->add($this->getTestResourcePath('test.jpg'), .25);
            $animation->add($this->getTestResourcePath('test.jpg'), .25);
        })->setLoops(5);

        $image->modify(new PlaceModifier($this->getTestResourcePath('circle.png'), 'top-right', 0, 0));

        foreach ($image as $frame) {
            $this->assertEquals('32250d', $frame->toImage(new Driver())->pickColor(300, 25)->toHex());
        }
    }
}
