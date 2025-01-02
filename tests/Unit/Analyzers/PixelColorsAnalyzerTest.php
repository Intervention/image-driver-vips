<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Analyzers;

use Intervention\Image\Collection;
use Intervention\Image\Drivers\Vips\Analyzers\PixelColorsAnalyzer;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Interfaces\ColorInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PixelColorsAnalyzer::class)]
class PixelColorsAnalyzerTest extends BaseTestCase
{
    public function testAnalyzeAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $analyzer = new PixelColorsAnalyzer(0, 0);
        $analyzer->setDriver(new Driver());
        $result = $analyzer->analyze($image);
        $this->assertInstanceOf(Collection::class, $result);
        $colors = array_map(fn(ColorInterface $color) => $color->toHex(), $result->toArray());
        $this->assertEquals($colors, ["394b63", "394b63", "394b63", "ffa601", "ffa601", "ffa601", "ffa601", "394b63"]);
    }

    public function testAnalyzeNonAnimated(): void
    {
        $image = $this->readTestImage('tile.png');
        $analyzer = new PixelColorsAnalyzer(0, 0);
        $analyzer->setDriver(new Driver());
        $result = $analyzer->analyze($image);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(ColorInterface::class, $result->first());
        $this->assertEquals('b4e000', $result->first()->toHex());
    }
}
