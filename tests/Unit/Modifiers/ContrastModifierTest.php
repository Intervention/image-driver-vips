<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Modifiers\ContrastModifier;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ContrastModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\ContrastModifier::class)]
final class ContrastModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->pickColor(14, 14)->toHex());
        $image->modify(new ContrastModifier(30));
        $this->assertEquals('0095eb', $image->pickColor(14, 14)->toHex());
    }
}
