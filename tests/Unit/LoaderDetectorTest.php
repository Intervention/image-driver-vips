<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Intervention\Image\Drivers\Vips\LoaderDetector;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;

class LoaderDetectorTest extends BaseTestCase
{
    public function testLoaders(): void
    {
        $result = LoaderDetector::create()->loaders();
        $this->assertTrue(count($result) > 1);
    }

    public function testFormats(): void
    {
        $result = LoaderDetector::create()->formats();
        $this->assertTrue(count($result) > 1);
    }
}
