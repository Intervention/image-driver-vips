<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\GammaModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GammaModifier::class)]
final class GammaModifierTest extends BaseTestCase
{
    public function testModifier(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertColor(0, 174, 240, 255, $image->pickColor(0, 0));
        $image->modify(new GammaModifier(2.1));
        $this->assertColor(0, 212, 247, 255, $image->pickColor(0, 0));
    }
}
