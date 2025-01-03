<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit;

use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Drivers\Vips\TrueTypeFont;

class TrueTypeFontTest extends BaseTestCase
{
    protected TrueTypeFont $font;

    public function setUp(): void
    {
        $this->font = new TrueTypeFont($this->getTestResourceData('test.ttf'));
    }

    public function testFamilyName(): void
    {
        $this->assertEquals('Intervention Test', $this->font->familyName());
    }
}
