<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\SharpenModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SharpenModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\SharpenModifier::class)]
final class SharpenModifierTest extends BaseTestCase
{
    public function testModify(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('60ab96', $image->pickColor(15, 14)->toHex());
        $image->modify(new SharpenModifier(10));
        $this->assertEquals('4daba7', $image->pickColor(15, 14)->toHex());
    }
}
