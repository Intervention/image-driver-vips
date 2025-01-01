<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\InvertModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InvertModifier::class)]
final class InvertModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->pickColor(0, 0)->toHex());
        $this->assertEquals('ffa601', $image->pickColor(25, 25)->toHex());
        $image->modify(new InvertModifier());
        $this->assertEquals('ff510f', $image->pickColor(0, 0)->toHex());
        $this->assertEquals('0059fe', $image->pickColor(25, 25)->toHex());
    }
}
