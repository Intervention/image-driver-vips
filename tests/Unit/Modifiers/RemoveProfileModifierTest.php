<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Modifiers\RemoveProfileModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\AnalyzerException;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageInterface;
use Jcupitt\Vips\Image as VipsImage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RemoveProfileModifier::class)]
final class RemoveProfileModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('icc.jpg');
        $image->modify(new RemoveProfileModifier());
        $this->expectException(AnalyzerException::class);
        $image->profile();
    }

    public function testApplyLeavesNoProfileInEncodedResult(): void
    {
        $image = $this->readTestImage('icc.jpg');
        $image->modify(new RemoveProfileModifier());

        $this->assertNotContains('icc-profile-data', $this->encodedFields($image));
    }

    /**
     * An image with no profile must be left strictly alone, it should not pay
     * for a copy nor lose its stashed source.
     */
    public function testApplyWithoutProfileIsANoop(): void
    {
        $image = $this->readTestImage('exif.jpg');
        $core = $image->core();
        $this->assertInstanceOf(Core::class, $core);
        $this->assertNotContains('icc-profile-data', $core->native()->getFields());

        $native = $core->native();
        $stash = $core->stashedSource();
        $this->assertNotNull($stash);

        $image->modify(new RemoveProfileModifier());

        $this->assertSame($native, $core->native());
        $this->assertSame($stash, $core->stashedSource());
    }

    /**
     * The removal must not leave the decoder's source ref stashed on the core.
     * A resize served from that ref reloads the original file, which brings the
     * profile straight back.
     */
    public function testRemovedProfileStaysRemovedAfterLaterModifiers(): void
    {
        $image = $this->readTestImage('icc.jpg');
        $core = $image->core();
        $this->assertInstanceOf(Core::class, $core);
        $this->assertNotNull($core->stashedSource());

        $image->modify(new RemoveProfileModifier());

        // assert the stash directly, going through resize() alone would stop
        // testing anything the day the resize fast path changes
        $this->assertNull($core->stashedSource());

        $image->resize(20, 20);

        $this->assertNotContains('icc-profile-data', $core->native()->getFields());
        $this->assertNotContains('icc-profile-data', $this->encodedFields($image));
    }

    /**
     * A clone shares its vips image with the original, so the removal has to
     * work on a copy. Removing the field in place would strip both.
     */
    public function testApplyOnCloneLeavesTheOriginalUntouched(): void
    {
        $image = $this->readTestImage('icc.jpg');
        $clone = clone $image;
        $clone->modify(new RemoveProfileModifier());

        $this->assertNotContains('icc-profile-data', $clone->core()->native()->getFields());
        $this->assertContains('icc-profile-data', $image->core()->native()->getFields());
        $this->assertContains('icc-profile-data', $this->encodedFields($image));
    }

    /**
     * Read back the header field names of the given image encoded as JPEG.
     *
     * @return list<string>
     */
    private function encodedFields(ImageInterface $image): array
    {
        return VipsImage::newFromBuffer(
            (string) $image->encodeUsingFormat(format: Format::JPEG),
        )->getFields();
    }
}
