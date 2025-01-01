<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\ImageManager;

class AlignRotationModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = (new ImageManager(Driver::class, autoOrientation: false))->read(
            $this->getTestResourcePath('orientation.jpg')
        );
        $this->assertColor(250, 2, 3, 255, $image->pickColor(3, 3));
        $result = $image->orient();
        $this->assertColor(1, 0, 254, 255, $image->pickColor(3, 3));
        $this->assertColor(1, 0, 254, 255, $result->pickColor(3, 3));
    }
}
