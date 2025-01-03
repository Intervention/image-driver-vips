<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Intervention\Image\Drivers\Vips\FontProcessor;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Typography\Font;

class FontProcessorTest extends BaseTestCase
{
    public function testBoxSizeTtf(): void
    {
        $processor = new FontProcessor();
        $size = $processor->boxSize(
            'ABC',
            $this->testFont()->setSize(120),
        );

        $this->assertInstanceOf(SizeInterface::class, $size);
        // $this->assertEquals(163, $size->width());
        // $this->assertEquals(72, $size->height());
    }

    private function testFont(): Font
    {
        return new Font($this->getTestResourcePath('test.ttf'));
    }
}
