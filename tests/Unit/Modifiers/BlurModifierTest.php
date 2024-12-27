<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\BlurModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BlurModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\BlurModifier::class)]
final class BlurModifierTest extends BaseTestCase
{
    public function testColorChange(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->pickColor(14, 14)->toHex());
        $image->modify(new BlurModifier(30));
        $this->assertEquals('43acb2', $image->pickColor(14, 14)->toHex());
    }
}
