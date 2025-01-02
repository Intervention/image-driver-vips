<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\ProfileRemovalModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\ColorException;

class ProfileRemovalModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('icc.jpg');
        $image->modify(new ProfileRemovalModifier());
        $this->expectException(ColorException::class);
        $image->profile();
    }
}
