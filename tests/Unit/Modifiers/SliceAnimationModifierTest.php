<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;

class SliceAnimationModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(8, $image->count());
        $result = $image->sliceAnimation(4, 2);
        $this->assertEquals(2, $image->count());
        $this->assertEquals(2, $result->count());
    }
}
