<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\StripMetaModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;

class StripMetaModifierTest extends BaseTestCase
{
    public function testStrip(): void
    {
        $image = $this->readTestImage('exif.jpg');
        $this->assertEquals('Oliver Vogel', $image->exif('IFD0.Artist'));
        $image->modify(new StripMetaModifier());
        $this->assertNull($image->exif('IFD0.Artist'));
        $result = $image->toJpeg();
        $this->assertEmpty(exif_read_data($result->toFilePointer())['IFD0.Artist'] ?? null);
    }
}
