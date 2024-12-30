<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Analyzers;

use Intervention\Image\Colors\Rgb\Colorspace as Rgb;
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
        $image = $this->readTestImage('tile.png');
        $analyzer = new ColorspaceAnalyzer();
        $analyzer->setDriver(new Driver());
        $result = $analyzer->analyze($image);
        $this->assertInstanceOf(ColorspaceInterface::class, $result);
        $this->assertInstanceOf(Rgb::class, $result);
    }
}
