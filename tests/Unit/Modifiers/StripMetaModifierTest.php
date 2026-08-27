<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Generator;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Modifiers\StripMetaModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageInterface;
use Jcupitt\Vips\Image as VipsImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(StripMetaModifier::class)]
final class StripMetaModifierTest extends BaseTestCase
{
    public function testStrip(): void
    {
        $image = $this->readTestImage('exif.jpg');
        $this->assertEquals('Oliver Vogel', $image->exif('IFD0.Artist'));
        $image->modify(new StripMetaModifier());
        $this->assertNull($image->exif('IFD0.Artist'));
        $result = $image->encodeUsingFormat(format: Format::JPEG);
        $this->assertEmpty(exif_read_data($result->toStream())['IFD0.Artist'] ?? null);
    }

    public function testStripRemovesMetaDataFromImage(): void
    {
        $stripped = [
            'exif-data',
            'exif-ifd0-Artist',
            'iptc-data',
            'jpeg-thumbnail-data',
            'xmp-data',
        ];

        $image = $this->readTestImage('exif.jpg');
        $fields = $image->core()->native()->getFields();

        foreach ($stripped as $field) {
            $this->assertContains($field, $fields);
        }

        $image->modify(new StripMetaModifier());
        $fields = $image->core()->native()->getFields();

        foreach ($stripped as $field) {
            $this->assertNotContains($field, $fields);
        }
    }

    public function testStripKeepsStructuralFields(): void
    {
        $image = $this->readTestImage('exif.jpg');
        $image->modify(new StripMetaModifier());
        $fields = $image->core()->native()->getFields();

        foreach (['xres', 'yres', 'interpretation', 'resolution-unit', 'orientation'] as $field) {
            $this->assertContains($field, $fields);
        }
    }

    public function testStripRemovesTextChunks(): void
    {
        $image = $this->readTestImage('tile.png');
        $this->assertContains('png-comment-0-Software', $image->core()->native()->getFields());
        $image->modify(new StripMetaModifier());
        $this->assertNotContains('png-comment-0-Software', $image->core()->native()->getFields());
    }

    /**
     * libvips writes an EXIF block that it builds from the image's core fields
     * at save time, whether or not the image carries one. Only the save
     * operation itself can keep it out of the result.
     */
    #[DataProvider('formatProvider')]
    public function testStripLeavesNoMetaDataInEncodedResult(Format $format): void
    {
        $image = $this->readTestImage('exif.jpg');
        $image->modify(new StripMetaModifier());

        $this->assertNotContains(
            'exif-data',
            $this->encodedHeaderFields($image, $format),
            'Failed asserting that the encoded ' . $format->name . ' carries no EXIF block.',
        );
    }

    public static function formatProvider(): Generator
    {
        yield 'jpeg' => [Format::JPEG];
        yield 'png' => [Format::PNG];
        yield 'webp' => [Format::WEBP];
        yield 'avif' => [Format::AVIF];
        yield 'tiff' => [Format::TIFF];
    }

    /**
     * The strip must not leave the decoder's source ref stashed on the core.
     * A resize served from that ref reloads the original file, which would put
     * the removed fields straight back on the image.
     */
    public function testStripClearsStashedSourceSoLaterModifiersStayStripped(): void
    {
        $image = $this->readTestImage('exif.jpg');
        $core = $image->core();
        $this->assertInstanceOf(Core::class, $core);
        $this->assertNotNull($core->stashedSource());

        $image->modify(new StripMetaModifier());
        $this->assertNull($core->stashedSource());

        $image->resize(10, 10);
        $this->assertNotContains('exif-data', $core->native()->getFields());
        $this->assertNotContains('exif-data', $this->encodedHeaderFields($image, Format::JPEG));
    }

    /**
     * A clone shares its vips image with the original, so the strip has to work
     * on a copy. Removing the fields in place would empty both.
     */
    public function testStripOnCloneLeavesTheOriginalUntouched(): void
    {
        $image = $this->readTestImage('exif.jpg');
        $clone = clone $image;
        $clone->modify(new StripMetaModifier());

        $this->assertNotContains('exif-data', $clone->core()->native()->getFields());
        $this->assertContains('exif-data', $image->core()->native()->getFields());
        $this->assertEquals('Oliver Vogel', $image->exif('IFD0.Artist'));
        $this->assertContains('exif-data', $this->encodedHeaderFields($image, Format::JPEG));
    }

    public function testStripKeepsProfile(): void
    {
        $image = $this->readTestImage('icc.jpg');
        $this->assertContains('icc-profile-data', $image->core()->native()->getFields());
        $image->modify(new StripMetaModifier());

        $fields = $this->encodedHeaderFields($image, Format::JPEG);
        $this->assertContains('icc-profile-data', $fields);
        $this->assertNotContains('exif-data', $fields);
    }

    public function testStripKeepsAnimation(): void
    {
        $image = $this->readTestImage('animation.gif');
        $this->assertEquals(8, $image->count());
        $image->modify(new StripMetaModifier());

        $this->assertTrue($image->isAnimated());
        // count() reads n-pages, which stayed at 8 even when the image held a
        // single frame, so the frames themselves have to be reached as well
        $this->assertEquals(8, $image->count());
        $this->assertEquals(8, iterator_count($image->core()));
        $this->assertEquals(15, $image->core()->frame(7)->native()->height);

        $result = VipsImage::newFromBuffer(
            (string) $image->encodeUsingFormat(format: Format::GIF),
            'n=-1',
        );
        $this->assertEquals(8, $result->get('n-pages'));
        $this->assertEquals(15, $result->get('page-height'));
        $this->assertEquals([200, 200, 200, 200, 200, 200, 200, 200], $result->get('delay'));
        $this->assertEquals(3, $result->get('loop'));
    }

    /**
     * Read back the header field names of the given image encoded in the given
     * format. Meta data lives in the header, so the first page is enough.
     *
     * @return list<string>
     */
    private function encodedHeaderFields(ImageInterface $image, Format $format): array
    {
        return VipsImage::newFromBuffer(
            (string) $image->encodeUsingFormat(format: $format),
        )->getFields();
    }
}
