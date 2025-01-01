<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Analyzers;

use Intervention\Image\Drivers\Vips\Analyzers\ResolutionAnalyzer;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Resolution;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResolutionAnalyzer::class)]
final class ResolutionAnalyzerTest extends BaseTestCase
{
    public function testAnalyze(): void
    {
        $image = $this->readTestImage('300dpi.png');
        $analyzer = new ResolutionAnalyzer();
        $analyzer->setDriver(new Driver());
        $result = $analyzer->analyze($image);
        $this->assertInstanceOf(Resolution::class, $result);
        $this->assertEquals(300, $result->perInch()->x());
        $this->assertEquals(300, $result->perInch()->y());
    }
}
