<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Analyzers;

use Intervention\Image\Drivers\Vips\Analyzers\PixelColorAnalyzer;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Jcupitt\Vips\BandFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use Intervention\Image\Interfaces\ColorInterface;

#[CoversClass(PixelColorAnalyzer::class)]
final class PixelColorAnalyzerTest extends BaseTestCase
{
    public function testAnalyze(): void
    {
        $image = $this->readTestImage('tile.png');
        $analyzer = new PixelColorAnalyzer(0, 0);
        $analyzer->setDriver(new Driver());
        $result = $analyzer->analyze($image);
        $this->assertInstanceOf(ColorInterface::class, $result);
        $this->assertEquals('b4e000', $result->toHex());
    }

    public function testAnalyzeFloatFormat(): void
    {
        $analyzer = new PixelColorAnalyzer(0, 0);
        $analyzer->setDriver(new Driver());

        foreach ([BandFormat::FLOAT, BandFormat::UCHAR] as $format) {
            $image = $this->readTestImage('trim.png');

            $image->core()->setNative(
                $image->core()->native()
                    ->linear(1, 30)
                    ->cast($format)
            );

            $result = $analyzer->analyze($image);
            $this->assertEquals('1eccff', $result->toHex(), 'Failed for format: ' . $format);
        }
    }
}
