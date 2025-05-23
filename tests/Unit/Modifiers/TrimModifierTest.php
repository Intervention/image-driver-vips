<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Modifiers\TrimModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\NotSupportedException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TrimModifier::class)]
final class TrimModifierTest extends BaseTestCase
{
    public function testTrim(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());
        $image->modify(new TrimModifier());
        $this->assertEquals(28, $image->width());
        $this->assertEquals(28, $image->height());
    }

    public function testTrimApple(): void
    {
        $image = $this->readTestImage('apple.jpg');
        $image->modify(new TrimModifier());
        $this->assertEquals(81, $image->width());
        $this->assertEquals(91, $image->height());
    }

    public function testTrimGradient(): void
    {
        $image = $this->readTestImage('radial.png');
        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());
        $image->modify(new TrimModifier(50));
        $this->assertEquals(37, $image->width());
        $this->assertEquals(37, $image->height());
    }

    public function testTrimHighTolerance(): void
    {
        $image = $this->readTestImage('trim.png');
        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());
        $image->modify(new TrimModifier(1000000));
        $this->assertEquals(1, $image->width());
        $this->assertEquals(1, $image->height());
    }

    public function testTrimAnimated(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->expectException(NotSupportedException::class);
        $image->modify(new TrimModifier());
    }
}
