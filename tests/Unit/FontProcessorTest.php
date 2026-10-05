<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Intervention\Image\Drivers\Vips\FontProcessor;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Geometry\Point;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Typography\Font;
use Intervention\Image\Typography\TextBlock;
use Jcupitt\Vips\Image as VipsImage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

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

    public function testTextToVipsImage(): void
    {
        $processor = new FontProcessor();
        $this->assertInstanceOf(VipsImage::class, $processor->textToVipsImage('test', $this->testFont()));
    }

    /**
     * @param array<array{string, string}> $faces
     */
    #[DataProvider('fontFacesProvider')]
    #[RunInSeparateProcess]
    public function testTextToVipsImageSelectsFontFace(array $faces): void
    {
        $processor = new FontProcessor();

        foreach ($faces as $index => [$filename, $description]) {
            $font = new Font($this->getTestResourcePath($filename));
            $font->setSize(40);
            $font->setLineHeight(1.62);
            $text = str_repeat('ABC ', $index + 1);
            $actual = $processor->textToVipsImage($text, $font);
            $expected = VipsImage::text(
                '<span line_height="1" foreground="#000000">' . $text . '</span>',
                [
                    'fontfile' => $font->filepath(),
                    'font' => $description . ' 40',
                    'dpi' => 72,
                    'rgba' => true,
                ],
            );

            $this->assertSame($expected->width, $actual->width, $filename);
            $this->assertSame($expected->height, $actual->height, $filename);
            $this->assertSame($expected->writeToMemory(), $actual->writeToMemory(), $filename);
        }
    }

    /**
     * @return array<string, array{array<array{string, string}>}>
     */
    public static function fontFacesProvider(): array
    {
        $regular = ['test.ttf', 'Intervention Test, Regular'];
        $bold = ['test-bold.ttf', 'Intervention Test, Bold'];
        $medium = ['test-medium.ttf', 'Intervention Test, Medium'];

        return [
            'regular first' => [[$regular, $bold, $medium, $regular, $bold]],
            'bold first' => [[$bold, $regular, $medium, $bold, $regular]],
        ];
    }

    private function testFont(): Font
    {
        return new Font($this->getTestResourcePath('test.ttf'));
    }
}
