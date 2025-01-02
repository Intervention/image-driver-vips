<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Analyzers;

use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Drivers\Vips\Analyzers\ColorspaceAnalyzer;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Interfaces\ColorspaceInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ColorspaceAnalyzer::class)]
final class ColorspaceAnalyzerTest extends BaseTestCase
{
    public function testAnalyze(): void
    {
        $image = $this->readTestImage('cmyk.jpg');
        $analyzer = new ColorspaceAnalyzer();
        $analyzer->setDriver(new Driver());
        $result = $analyzer->analyze($image);
        $this->assertInstanceOf(ColorspaceInterface::class, $result);
        $this->assertInstanceOf(CmykColorspace::class, $result);
    }
}
