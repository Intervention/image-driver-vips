<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Source\PathSource;
use Intervention\Image\Exceptions\DirectoryNotFoundException;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\FileNotFoundException;
use Intervention\Image\Exceptions\FileNotReadableException;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Traits\CanDetectImageSources;
use Intervention\Image\Traits\CanParseFilePath;
use Jcupitt\Vips;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

class FilePathImageDecoder extends NativeObjectDecoder
{
    use CanDetectImageSources;
    use CanParseFilePath;

    /**
     * {@inheritdoc}
     *
     * @see DecoderInterface::supports()
     */
    public function supports(mixed $input): bool
    {
        return $this->couldBeFilePath($input);
    }

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::decode()
     *
     * @throws InvalidArgumentException
     * @throws DirectoryNotFoundException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws DriverException
     * @throws StateException
     * @throws ImageDecoderException
     */
    public function decode(mixed $input): ImageInterface
    {
        $path = $this->readableFilePathOrFail($input);

        try {
            $vipsImage = Vips\Image::newFromFile($path . '[' . $this->stringOptions() . ']', [
                'access' => Vips\Access::SEQUENTIAL,
            ]);
        } catch (VipsException $e) {
            throw new ImageDecoderException(
                'Failed to decode image',
                previous: $e
            );
        }

        $stashable = $this->isStashableSource($vipsImage);

        $image = parent::decode($vipsImage);

        // stash the source ref so resize-family modifiers can swap to thumbnail()
        $core = $image->core();
        if ($stashable && $core instanceof Core) {
            $core->setStashedSource(new PathSource($path, $this->stringOptions()));
        }

        // set file path on origin
        $image->origin()->setFilePath($path);

        // extract exif data for the appropriate formats
        if (in_array($this->vipsMediaType($vipsImage)?->format(), [Format::JPEG, Format::TIFF])) {
            $image->setExif($this->extractExifData($path));
        }

        return $image;
    }

    /**
     * Return true if the source is in a state where we can safely stash
     * it for the resize-family modifiers' thumbnail* fast path.
     *
     * Skip stashing when the parent decoder will mutate the in-memory
     * VipsImage in a way that makes the stashed source no longer reflect
     * the resulting image (auto-orient, BW/GREY16 to SRGB icc_transform).
     * The bandjoin_const(255) for SRGB-3-band sources is OK to stash
     * because resize modifiers can re-apply the same alpha if needed.
     *
     * @throws StateException
     */
    private function isStashableSource(VipsImage $vipsImage): bool
    {
        if (in_array($vipsImage->interpretation, [Interpretation::B_W, Interpretation::GREY16], true)) {
            return false;
        }

        if (
            $this->driver()->config()->autoOrientation === true
            && ($this->exifRotation($vipsImage) ?? 1) > 1
        ) {
            return false;
        }

        return true;
    }
}
