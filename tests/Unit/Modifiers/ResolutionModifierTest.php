<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\ResolutionModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResolutionModifier::class)]
final class ResolutionModifierTest extends BaseTestCase
{
    public function testResolutionChange(): void
    {
        $image = $this->readTestImage('300dpi.png');
        $this->assertEquals(300.0, $image->resolution()->perInch()->x());
        $this->assertEquals(300.0, $image->resolution()->perInch()->y());
        $image->modify(new ResolutionModifier(1, 2));
        $this->assertEquals(1.0, $image->resolution()->perInch()->x());
        $this->assertEquals(2.0, $image->resolution()->perInch()->y());
    }
}
