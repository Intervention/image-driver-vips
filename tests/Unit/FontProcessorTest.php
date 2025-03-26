<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Intervention\Image\Drivers\Vips\FontProcessor;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Geometry\Point;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Typography\Font;
use Intervention\Image\Typography\TextBlock;

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
        $this->assertEquals(155, $size->width());
        $this->assertEquals(44, $size->height());
    }

    public function testNativeFontSize(): void
    {
        $processor = new FontProcessor();
        $font = new Font();
        $font->setSize(14.2);
        $size = $processor->nativeFontSize($font);
        $this->assertEquals(14.2, $size);
    }

    public function testTextBlock(): void
    {
        $processor = new FontProcessor();
        $result = $processor->textBlock('test', $this->testFont(), new Point(0, 0));
        $this->assertInstanceOf(TextBlock::class, $result);
    }

    public function testTypographicalSize(): void
    {
        $processor = new FontProcessor();
        $result = $processor->typographicalSize($this->testFont());
        $this->assertEquals(13, $result);
    }

    public function testCapHeight(): void
    {
        $processor = new FontProcessor();
        $result = $processor->capHeight($this->testFont());
        $this->assertEquals(10, $result);
    }

    public function testLeading(): void
    {
        $processor = new FontProcessor();
        $result = $processor->leading($this->testFont());
        $this->assertEquals(16, $result);
    }

    private function testFont(): Font
    {
        return new Font($this->getTestResourcePath('test.ttf'));
    }
}
