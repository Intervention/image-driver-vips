<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Source\BufferSource;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Traits\CanDetectImageSources;
use Jcupitt\Vips;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use Stringable;

class BinaryImageDecoder extends NativeObjectDecoder
{
    use CanDetectImageSources;

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::supports()
     */
    public function supports(mixed $input): bool
    {
        return $this->couldBeBinaryData($input);
    }

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::decode()
     *
     * @throws InvalidArgumentException
     * @throws ImageDecoderException
     * @throws DecoderException
     * @throws StateException
     */
    public function decode(mixed $input): ImageInterface
    {
        if (!is_string($input) && !$input instanceof Stringable) {
            throw new InvalidArgumentException(
                'Image source must be binary data of type string or instance of Stringable',
            );
        }

        $input = (string) $input;

        if ($input === '') {
            throw new InvalidArgumentException('Unable to decode binary data from empty string');
        }

        try {
            $vipsImage = Vips\Image::newFromBuffer($input, $this->stringOptions(), [
                'access' => Vips\Access::SEQUENTIAL,
            ]);
        } catch (VipsException $e) {
            throw new ImageDecoderException('Failed to decode unsupported image format from binary data', previous: $e);
        }

        $stashable = $this->isStashableSource($vipsImage);

        $image = parent::decode($vipsImage);

        // stash the source ref so resize-family modifiers can swap to thumbnail_buffer()
        $core = $image->core();
        if ($stashable && $core instanceof Core) {
            $core->setStashedSource(new BufferSource($input, $this->stringOptions()));
        }

        // get media type enum from string media type
        $format = Format::tryCreate($image->origin()->mediaType());

        // extract exif data for appropriate formats
        if (in_array($format, [Format::JPEG, Format::TIFF])) {
            $image->setExif($this->extractExifData($input));
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
