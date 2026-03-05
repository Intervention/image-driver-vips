<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\StripMetaModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Format;

class StripMetaModifierTest extends BaseTestCase
{
    public function testStrip(): void
    {
        $image = $this->readTestImage('exif.jpg');
        $this->assertEquals('Oliver Vogel', $image->exif('IFD0.Artist'));
        $image->modify(new StripMetaModifier());
        $this->assertNull($image->exif('IFD0.Artist'));
        $result = $image->encodeUsingFormat(format: Format::JPEG);
        $this->assertEmpty(exif_read_data($result->toStream())['IFD0.Artist'] ?? null);
    }
}
