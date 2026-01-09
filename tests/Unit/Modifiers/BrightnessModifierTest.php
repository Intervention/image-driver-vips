<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\BrightnessModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BrightnessModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\BrightnessModifier::class)]
final class BrightnessModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->colorAt(14, 14)->toHex());
        $image->modify(new BrightnessModifier(30));
        $this->assertEquals('1eccff', $image->colorAt(14, 14)->toHex());
    }
}
