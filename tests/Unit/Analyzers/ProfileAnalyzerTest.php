<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Analyzers;

use Intervention\Image\Colors\Profile;
use Intervention\Image\Drivers\Vips\Analyzers\ProfileAnalyzer;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\ColorException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProfileAnalyzer::class)]
final class ProfileAnalyzerTest extends BaseTestCase
{
    public function testAnalyze(): void
    {
        $image = $this->readTestImage('icc.jpg');
        $analyzer = new ProfileAnalyzer();
        $analyzer->setDriver(new Driver());
        $result = $analyzer->analyze($image);
        $this->assertInstanceOf(Profile::class, $result);
    }

    public function testAnalyzeNoProfile(): void
    {
        $image = $this->readTestImage('tile.png');
        $analyzer = new ProfileAnalyzer();
        $analyzer->setDriver(new Driver());
        $this->expectException(ColorException::class);
        $analyzer->analyze($image);
    }
}
