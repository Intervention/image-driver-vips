<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Generator;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Drivers\Vips\TrueTypeFont;
use PHPUnit\Framework\Attributes\DataProvider;

class TrueTypeFontTest extends BaseTestCase
{
    #[DataProvider('familyNamesProvider')]
    public function testFamilyNames(
        string $filename,
        string $family,
        string $subfamily,
    ): void {
        $font = TrueTypeFont::fromPath($this->getTestResourcePath($filename));
        $this->assertEquals($family, $font->familyName());
        $this->assertEquals($subfamily, $font->subfamilyName());
    }

    public static function familyNamesProvider(): Generator
    {
        yield ['test.ttf', 'Intervention Test', 'Regular'];
        yield ['test-bold.ttf', 'Intervention Test', 'Bold'];
        yield ['test-bolditalic.ttf', 'Intervention Test', 'Bold Italic'];
        yield ['test-italic.ttf', 'Intervention Test', 'Italic'];
        yield ['test-medium.ttf', 'Intervention Test', 'Medium'];
        yield ['test-semibold.ttf', 'Intervention Test', 'SemiBold'];
        yield ['test-custom.ttf', 'Intervention Test', 'Custom'];
    }

    #[DataProvider('typographicFamilyNamesProvider')]
    public function testTypographicFamilyNames(
        string $filename,
        string $typographicFamily,
        string $typographicSubfamily,
    ): void {
        $font = TrueTypeFont::fromPath($this->getTestResourcePath($filename));
        $this->assertEquals($typographicFamily, $font->typographicFamilyName());
        $this->assertEquals($typographicSubfamily, $font->typographicSubfamilyName());
    }

    public static function typographicFamilyNamesProvider(): Generator
    {
        yield ['test.ttf', 'Intervention Test', 'Regular'];
        yield ['test-bold.ttf', 'Intervention Test', 'Bold'];
        yield ['test-bolditalic.ttf', 'Intervention Test', 'Bold Italic'];
        yield ['test-italic.ttf', 'Intervention Test', 'Italic'];
        yield ['test-medium.ttf', 'Intervention Test', 'Medium'];
        yield ['test-semibold.ttf', 'Intervention Test', 'SemiBold'];
        yield ['test-custom.ttf', 'Intervention Test', 'Custom'];
    }
}
