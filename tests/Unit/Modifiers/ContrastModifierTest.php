<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Image;
use Intervention\Image\Modifiers\ContrastModifier;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ContrastModifier::class)]
#[CoversClass(\Intervention\Image\Drivers\Vips\Modifiers\ContrastModifier::class)]
final class ContrastModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals('00aef0', $image->colorAt(14, 14)->toHex());
        $image->modify(new ContrastModifier(30));
        $this->assertEquals('00bbff', $image->colorAt(14, 14)->toHex());
    }

    /**
     * The contrast adjustment pivots on mid grey: a positive level pushes the
     * tones away from it, a negative level pulls them towards it, and mid
     * grey itself stays where it is.
     *
     * @return array<string, array{int, int, float}>
     */
    public static function eightBitProvider(): array
    {
        return [
            'dark tone gets darker' => [50, 80, 56.25],
            'mid grey stays' => [50, 128, 128.25],
            'light tone gets lighter' => [50, 175, 198.75],
            'black lifts towards mid grey' => [-50, 0, 63.75],
            'white drops towards mid grey' => [-50, 255, 191.25],
        ];
    }

    #[DataProvider('eightBitProvider')]
    public function testApplyPivotsOnMidGrey(int $level, int $tone, float $expected): void
    {
        $image = new Image(new Driver(), new Core(self::vipsImage(4, 4, [$tone, $tone, $tone])));
        $image->modify(new ContrastModifier($level));

        foreach ($image->core()->native()->getpoint(1, 1) as $value) {
            $this->assertEqualsWithDelta($expected, $value, 1);
        }
    }

    #[DataProvider('eightBitProvider')]
    public function testApplyPivotsOnMidGreyWithAlpha(int $level, int $tone, float $expected): void
    {
        $image = new Image(new Driver(), new Core(self::vipsImage(4, 4, [$tone, $tone, $tone, 255])));
        $image->modify(new ContrastModifier($level));

        [$r, $g, $b, $a] = $image->core()->native()->getpoint(1, 1);
        $this->assertEqualsWithDelta($expected, $r, 1);
        $this->assertEqualsWithDelta($expected, $g, 1);
        $this->assertEqualsWithDelta($expected, $b, 1);
        $this->assertEquals(255, $a);
    }

    /**
     * On a 16-bit source the pivot is mid grey on the 16-bit scale, not 127.5.
     */
    public function testApplyPivotsOnMidGreySixteenBit(): void
    {
        $native = VipsImage::black(4, 4, ['bands' => 3])
            ->add(45000)
            ->cast(BandFormat::USHORT)
            ->copy(['interpretation' => Interpretation::RGB16]);

        $image = new Image(new Driver(), new Core($native));
        $image->modify(new ContrastModifier(50));

        $this->assertEquals(BandFormat::USHORT, $image->core()->native()->format);
        foreach ($image->core()->native()->getpoint(1, 1) as $value) {
            $this->assertEqualsWithDelta(51116.25, $value, 1);
        }
    }
}
